<?php
namespace booosta\formchecker;

\booosta\Framework::add_module_trait('webapp', 'formchecker\webapp');

trait webapp
{
  protected function add_formchecker($checker)
  {
    if(!is_object($checker) || !is_a($checker, "\\booosta\\formchecker\\Formchecker")) return false;
    $this->add_includes($checker->get_javascript(true));
  }

  protected function preparse_formchecker()
  {
    if(!$this->moduleinfo['formchecker'])
      $this->add_javascript("function checkForm() { return true; } ");
  }
}
