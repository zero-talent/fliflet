<?php

  require_once( DIR_LIB . '/i_application.inc.php' );
  require_once( DIR_LIB . '/database.inc.php' );
  require_once( DIR_LIB . '/template.inc.php' );

  class AppLatex implements IApplication {

    private $mApp;

    public function __construct( $app ) {
      $this->mApp = $app;
    }

    public function doPreOperations() {
      $this->mApp->doPreOperations();
    }

    public function tpl( $idt, $data = null, $returnResult = false ) {
      switch( $idt ) {
        case 'main':
          echo( $this->getLatex() );
          break;
        case 'appendix_examples_row':
          $curRec = Template::getCurrentRecord();
          $tpl = new Template( 'latex_appendix_examples_row' );
          return $tpl->render( $curRec[ 'rows' ] );
        case 'tables_row_1':
          $curRec = Template::getCurrentRecord();
          $tpl = new Template( 'latex_tables_row_1' );
          return $tpl->render( $curRec[ 'table_1' ] );
        default:
          return $this->mApp->tpl( $idt, $data, $returnResult );
      }
    }

    private function getLatex() {
      global $argc, $argv;
      $doc = $argc > 1 ? $argv[ 2 ] : '';
      switch( $doc ) {
        case 'appendix':
          return $this->getLatexAppendix();
        case 'appendix_examples':
          return $this->getLatexExamples();
        case 'tables':
          return $this->getLatexTables();
        default:
          ;
      }
    }

    private function getLatexAppendix() {
      $db = Factory::getDatabase();
      $suppliers = $db->getReportSuppliers();
      $tpl = new Template( 'latex_appendix_section' );
      return $tpl->render( $suppliers );
    }

    private function getLatexExamples() {
      $db = Factory::getDatabase();
      $examples = $db->getMostDelayed();
      $tpl = new Template( 'latex_appendix_examples' );
      return $tpl->render( $examples );
    }

    private function getLatexTables() {
      $db = Factory::getDatabase();
      $tables = $db->getTables();
      $labels = $tables[ 'table_1' ][ 'labels' ];
      $data = array( 'table_1' => array() );
      for( $i = 0; $i < count( $labels ); $i++ ) {
        $data[ 'table_1' ][ $i ] = array( 'label' => $labels[ $i ], 'mean_value' => $tables[ 'table_1' ][ 'datasets' ][ 0 ][ 'data' ][ $i ] );
      }
      $tpl = new Template( 'latex_tables' );
      return $tpl->render( array( $data ) );
    }

    public function doPostOperations() {
      $this->mApp->doPostOperations();
    }
  }

?>