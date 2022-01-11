<?php
namespace booosta\formchecker;

use \booosta\Framework as b;
b::init_module('formchecker');

class Formchecker extends \booosta\base\Module
{
  use moduletrait_formchecker;

  private $formname;
  private $funcname;
  private $required_fields;
  private $number_fields;
  private $email_fields;
  private $maxnumber_fields;
  private $regexp_fields;
  private $equal_fields;
  private $errormessage;
  private $extra;


  public function __construct($formname = 'form0', $funcname = 'checkForm')
  {
    parent::__construct();

    $this->formname = $formname;
    $this->funcname = $funcname;
    $this->required_fields = [];
    $this->number_fields = [];
    $this->email_fields = [];
    $this->maxnumber_fields = [];
    $this->regexp_fields = [];
    $this->equal_fields = [];
    $this->errormessage = [];
  }

  public function after_instanciation()
  {
    parent::after_instanciation();

    if(is_object($this->topobj) && is_a($this->topobj, "\\booosta\\webapp\\Webapp")):
      $this->topobj->moduleinfo['formchecker'] = true;
    endif;
  }


  public function add_required_field($field) { $this->required_fields[] = $field; }
  public function add_number_field($field) { $this->number_fields[] = $field; }
  public function add_email_field($field) { $this->email_fields[] = $field; }
  public function add_maxnumber_field($field, $max) { $this->maxnumber_fields[$field] = $max; }
  public function add_regexp_field($field, $regexp) { $this->regexp_fields[$field] = $regexp; }
  public function add_equal_field($field1, $field2) { $this->equal_fields[$field1] = $field2; }
  public function add_extra($code) { $this->extra .= $code; }

  public function add_fields($type, $fields, $prefix = null)
  {
    $func = "add_{$type}_field";
    if(!is_array($fields)):
      $fields = str_replace(' ', '', $fields);
      $fields = explode(',', $fields);
    endif;

    foreach($fields as $field):
      if($prefix) $field = $prefix . "[$field]";
      $this->$func($field);
    endforeach;
  }

  public function set_errormessage($type, $msg) { $this->errormessage[$type] = $msg; }


  public function get_javascript($add_tags = false)
  {
    $ret = "function $this->funcname (){\n";

    if($this->errormessage['required_field']) $errmsg = $this->errormessage['required_field'];
    else $errmsg = $this->t('Required field missing') . ': {field}';

    foreach($this->required_fields as $field):
      if(strstr($field, ' && ') || strstr($field, ' || ') || strstr($field, ' AND ') || strstr($field, ' OR ')):
        $field = str_replace(' OR ', ' || ', $field);
        $field = str_replace(' AND ', ' && ', $field);
        $tmp = preg_replace_callback('/[A-Za-z0-9_]+/', function($m){ $this->convert($m[0]); }, $field);
        $tmp = str_replace('_FORMNAME_', $this->formname, $tmp);
        $ret .= "  if(!($tmp)) {\n";
        $err = str_replace('{field}', '', $errmsg);
        $ret .= "  alert(unescape(\"$err\"));\n";
        $ret .= "  return false;\n";
        $ret .= "  }\n\n";
      else:
        $err = str_replace('{field}', $field, $errmsg);
        $ret .= "  if(((document.getElementsByName('$field')[0].type == \"checkbox\") && \n";
        $ret .= "    (document.getElementsByName('$field')[0].checked == false)) || \n";
        $ret .= "    ((document.getElementsByName('$field')[0].type == \"radio\") && \n";
        $ret .= "    (document.querySelector('input[name=\"$field\"]:checked') == null)) || \n";
        $ret .= "    (document.getElementsByName('$field')[0].value == \"\")) {\n";
        $ret .= "    alert(unescape(\"$err\"));\n";
        $ret .= "    document.getElementsByName('$field')[0].focus();\n";
        $ret .= "    return false;\n";
        $ret .= "  }\n\n";
      endif;
    endforeach;

    if($this->errormessage['number_field']) $errmsg = $this->errormessage['number_field'];
    else $errmsg = $this->t('Field must be numeric') . ': {field}';

    foreach($this->number_fields as $field):
      $ret .= "  var ok = true;\n";
      $ret .= "  for(i=0; i<document.getElementsByName('$field')[0].value.length; ++i)\n";
      $ret .= "    if((document.getElementsByName('$field')[0].value.charAt(i) < \"0\" ||\n";
      $ret .= "       document.getElementsByName('$field')[0].value.charAt(i) > \"9\") &&\n";
      $ret .= "       document.getElementsByName('$field')[0].value.charAt(i) != \".\" &&\n";
      $ret .= "       document.getElementsByName('$field')[0].value.charAt(i) != \",\")\n";
      $ret .= "      ok = false;\n";
      $ret .= "  if(!ok) {\n";
      $err = str_replace("{field}", $field, $errmsg);
      $ret .= "    alert(unescape(\"$err\"));\n";
      $ret .= "    document.getElementsByName('$field')[0].focus();\n";
      $ret .= "    return false;\n";
      $ret .= "  }\n\n";
    endforeach;

    if($this->errormessage['maxnumber_field']) $errmsg = $this->errormessage['maxnumber_field'];
    else $errmsg2 = $this->t('Field exceeds max. value') . ': {field}';

    foreach($this->maxnumber_fields as $field=>$max):
      $ret .= "  var ok = true;\n";
      $ret .= "  for(i=0; i<document.getElementsByName('$field')[0].value.length; ++i)\n";
      $ret .= "    if(document.getElementsByName('$field')[0].value.charAt(i) < \"0\" ||\n";
      $ret .= "       document.getElementsByName('$field')[0].value.charAt(i) > \"9\")\n";
      $ret .= "      ok = false;\n";
      $ret .= "  if(!ok) {\n";
      $err = str_replace('{field}', $field, $errmsg);
      $ret .= "    alert(unescape(\"$err\"));\n";
      $ret .= "    document.getElementsByName('$field')[0].focus();\n";
      $ret .= "    return false;\n";
      $ret .= "  }\n\n";

      $ret .= "  if(document.getElementsByName('$field')[0].value > $max) {\n";
      $err = str_replace('{field}', $field, $errmsg2);
      $ret .= "    alert(unescape(\"$err\"));\n";
      $ret .= "    document.getElementsByName('$field')[0].focus();\n";
      $ret .= "    return false;\n";
      $ret .= "  }\n\n";
    endforeach;

    if($this->errormessage['regexp_field']) $errmsg = $this->errormessage['regexp_field'];
    else $errmsg = $this->t('Wrong format for field') . ' {field}';

    foreach($this->regexp_fields as $field=>$regexp):
      $ret .= "  if(!document.getElementsByName('$field')[0].value.match($regexp)) {\n";
      $err = str_replace('{field}', $field, $errmsg);
      $ret .= "    alert(unescape(\"$err\"));\n";
      $ret .= "    document.getElementsByName('$field')[0].focus();\n";
      $ret .= "    return false;\n";
      $ret .= "  }\n\n";
    endforeach;

    if($this->errormessage['email_field']) $errmsg = $this->errormessage['email_field'];
    else $errmsg = $this->t('Field is not in email format') . ': {field}';

    foreach($this->email_fields as $field):
      $ret .= "  if(!document.getElementsByName('$field')[0].value.match(/^[_a-zA-Z0-9-]+(\\.[_a-zA-Z0-9-]+)*@[a-zA-Z0-9-]+(\\.[a-zA-Z0-9]+)*$/)) {\n";
      $err = str_replace("{field}", $field, $errmsg);
      $ret .= "    alert(unescape(\"$err\"));\n";
      $ret .= "    document.getElementsByName('$field')[0].focus();\n";
      $ret .= "    return false;\n";
      $ret .= "  }\n\n";
    endforeach;

    if($this->errormessage['equal_field']) $errmsg = $this->errormessage['equal_field'];
    else $errmsg = $this->t('Fields must be equal') . ': {field1} and {field2}';

    foreach($this->equal_fields as $field1=>$field2):
      $ret .= " if(document.getElementsByName('$field1')[0].value != document.getElementsByName('$field2')[0].value) {\n";
      $err = str_replace("{field1}", $field1, $errmsg);
      $err = str_replace("{field2}", $field2, $err);
      $ret .= "    alert(unescape(\"$err\"));\n";
      $ret .= "    document.getElementsByName('$field1')[0].focus();\n";
      $ret .= "    return false;\n";
      $ret .= "  }\n\n";
    endforeach;

    if($this->extra):
      if($this->errormessage['extra']) $errmsg = $this->errormessage['extra'];
      else $errmsg = $this->t('Form validation failed');

      $ret .= $this->extra;
    endif;

    $ret .= "}\n";

    if($add_tags):
      $tag1 = "<script type='text/javascript'>";
      $tag2 = '</script>';
    else:
      $tag1 = $tag2 = '';
    endif;

    return "$tag1\n$ret\n$tag2";
  }

  private function convert($str) { return "(document._FORMNAME_.$str.value != \"\")"; }
}
