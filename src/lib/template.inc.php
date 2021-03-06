<?php
  define( 'TEMPLATE_TAG_PATTERN', '/%%([^%]+)%%/' );
  define( 'TEMPLATE_INC_PATTERN', '/##([^#]+)##/' );
  define( 'TEMPLATE_FUNCTION_PATTERN', '/^([^\\(]+)\\(([^\\)]*)\\)$/' );
  class Template {
    private static $mData = array();
    private $mTplText;
    public function __construct( $idt, $app = NULL ) {
      if( file_exists( $idt ) ) {
        $tplFile = $idt;
      }
      else {
        $tplFile = sprintf( '%s/%s.tpl', DIR_TPL, $idt );
      }
      $this->mTplText = file_exists( $tplFile ) ? file_get_contents( $tplFile ) : $idt;
    }
    public function render( $data ) {
      $res = '';
      if( isset( $data[ 0 ] ) ) {
        for( $i = 0; $i < count( $data ); $i++ ) {
          array_push( self::$mData, $data[ $i ] );
          $res .= $this->realRender( $data[ $i ] );
          array_pop( self::$mData );
        }
      }
      else {
        $res = $this->realRender( $data );
      }
      return $res;
    }
    public static function getCurrentRecord() {
      return current( self::$mData );
    }
    public function latex_escape( $str ) {
      $str = html_entity_decode( $str, ENT_HTML5, 'UTF-8' );
      $str = mb_decode_numericentity( $str, array( 0x0, 0x2FFFF, 0, 0xFFFF ), 'UTF-8');
      return preg_replace( '/[\\#%&_\{\}\~\^]/i', '\\\\\0', $str );
    }
    public function latex_truncate( $str, $len = 30 ) {
      if( strlen( $str ) > $len ) {
        $tmp = explode( "\n", wordwrap( $str, $len ) );
        $str = $tmp[ 0 ] . '\\ldots';
      }
      return $str;
    }
    private function realRender( $data ) {
      $resolved = array();
      // Expand template includes
      if( preg_match_all( TEMPLATE_INC_PATTERN, $this->mTplText, $m0, PREG_SET_ORDER ) ) {
        foreach( $m0 as $inc ) {
          if( !isset( $resolved[ $inc[ 0 ] ] ) ) {
            global $theApp;
            $resolved[ $inc[ 0 ] ] = $theApp->tpl( $inc[ 1 ], TRUE );
          }
        }
      }
      // Expand value tags
      if( preg_match_all( TEMPLATE_TAG_PATTERN, $this->mTplText, $m0, PREG_SET_ORDER ) ) {
        foreach( $m0 as $tag ) {
          $subject = $tag[ 0 ];
          if( !isset( $resolved[ $subject ] ) ) {
            $expression = trim( $tag[ 1 ] );
            $a = explode( '|', $expression );
            $n = count( $a );
            $field = trim( $a[ 0 ] );
            $functions = array();
            for( $i = 1; $i < $n; $i++ ) {
              if( preg_match( TEMPLATE_FUNCTION_PATTERN, trim( $a[ $i ] ), $m1 ) ) {
                $f = trim( $m1[ 1 ] );
                $p = trim( $m1[ 2 ] );
                $functions[ $f ] = ( $p == '' ? array() : array_map( 'trim', explode( ',', $p ) ) );
              }
            }
            $value = '';
            if( isset( $data[ $field ] ) ) {
              $value = $data[ $field ];
              foreach( $functions as $name => $params ) {
                if( function_exists( $name ) ) {
                  $value = call_user_func_array( $name, array_merge( array( $value ), $params ) );
                }
                else if( method_exists( $this, $name ) ) {
                  $value = call_user_func_array( array( $this, $name ), array_merge( array( $value ), $params ) );
                }
              }
            }
            $resolved[ $subject ] = $value;
          }
        }
      }
      return str_replace( array_keys( $resolved ), array_values( $resolved ), $this->mTplText );
    }
  }
?>
