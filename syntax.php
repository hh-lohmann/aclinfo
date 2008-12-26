<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Andreas Gohr <andi@splitbrain.org>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_aclinfo extends DokuWiki_Syntax_Plugin {

    /**
     * return some info
     */
    function getInfo(){
        return array(
            'author' => 'Andreas Gohr',
            'email'  => 'gohr@cosmocode.de',
            'date'   => '2008-12-26',
            'name'   => 'ACL Info Plugin',
            'desc'   => 'Displays information about the ACLs for the current page',
            'url'    => 'http://dokuwiki.org/plugin:aclinfo',
        );
    }

    /**
     * What kind of syntax are we?
     */
    function getType(){
        return 'substition';
    }

    /**
     * What about paragraphs?
     */
    function getPType(){
        return 'block';
    }

    /**
     * Where to sort in?
     */
    function getSort(){
        return 155;
    }


    /**
     * Connect pattern to lexer
     */
    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('~~ACLINFO~~',$mode,'plugin_aclinfo');
    }


    /**
     * Handle the match
     */
    function handle($match, $state, $pos, &$handler){
        return array();
    }

    /**
     * Create output
     */
    function render($format, &$R, $data) {
        global $INFO;
        if($format != 'xhtml') return false;

        $perms = $this->_aclcheck($INFO['id']);
        $R->listu_open();
        foreach((array)$perms as $who => $p){
            $R->listitem_open(1);
            $R->listcontent_open();
            $R->cdata(sprintf($this->getLang('perm'.$p), urldecode($who)));
            $R->listcontent_close();
            $R->listitem_close();
        }
        $R->listu_close();
        return true;
    }

    function _aclcheck($id){
        global $conf;
        global $AUTH_ACL;


        $ns    = getNS($id);
        $perms = array();

        //check exact match first
        $matches = preg_grep('/^'.preg_quote($id,'/').'\s+/',$AUTH_ACL);
        if(count($matches)){
            foreach($matches as $match){
                $match = preg_replace('/#.*$/','',$match); //ignore comments
                $acl   = preg_split('/\s+/',$match);
                if($acl[2] > AUTH_DELETE) $acl[2] = AUTH_DELETE; //no admins in the ACL!
                if(!isset($perms[$acl[1]])) $perms[$acl[1]] = $acl[2];
            }
        }

        //still here? do the namespace checks
        if($ns){
            $path = $ns.':\*';
        }else{
            $path = '\*'; //root document
        }

        do{
            $matches = preg_grep('/^'.$path.'\s+/',$AUTH_ACL);
            if(count($matches)){
                foreach($matches as $match){
                    $match = preg_replace('/#.*$/','',$match); //ignore comments
                    $acl   = preg_split('/\s+/',$match);
                    if($acl[2] > AUTH_DELETE) $acl[2] = AUTH_DELETE; //no admins in the ACL!
                    if(!isset($perms[$acl[1]])) $perms[$acl[1]] = $acl[2];
                }
            }

            //get next higher namespace
            $ns   = getNS($ns);
            //get next higher namespace
            $ns   = getNS($ns);

            if($path != '\*'){
                $path = $ns.':\*';
                if($path == ':\*') $path = '\*';
            }else{
                //we did this already
                //break here
                break;
            }
        }while(1); //this should never loop endless

        return $perms;
    }
}

//Setup VIM: ex: et ts=4 enc=utf-8 :