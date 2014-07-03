<?php
if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}

abstract class MeprBaseModel
{
  protected $rec;
  protected $attrs;
  
  public function __get($name)
  {
    if($this->magic_method_handler_exists($name))
      return $this->call_magic_method_handler('get',$name);

    $object_vars = array_keys(get_object_vars($this));
    $rec_array = (array)$this->rec;
    
    if(in_array($name, $object_vars))
      return $this->$name;
    else if(array_key_exists($name, $rec_array))
    {
      if(is_array($this->rec))
        return $this->rec[$name];
      else
        return $this->rec->$name;
    }
    else
      return null;
  }

  public function __set($name, $value)
  {
    if($this->magic_method_handler_exists($name))
      return $this->call_magic_method_handler('set', $name, $value);

    $object_vars = array_keys(get_object_vars($this));
    $rec_array = (array)$this->rec;
    
    if(in_array($name,$object_vars))
      $this->$name = $value;
    else if(array_key_exists($name, $rec_array))
    {
      if(is_array($this->rec))
        $this->rec[$name] = $value;
      else
        $this->rec->$name = $value;
    }
  }
  
  public function __isset($name)
  {
    if($this->magic_method_handler_exists($name))
      return $this->call_magic_method_handler('isset', $name);

    if(is_array($this->rec))
      return isset($this->rec[$name]);
    else if(is_object($this->rec))
      return isset($this->rec->$name);
    else
      return false;
  }
  
  public function __unset($name)
  {
    if($this->magic_method_handler_exists($name))
      return $this->call_magic_method_handler('unset', $name);

    if(is_array($this->rec))
      unset($this->rec[$name]);
    else if(is_object($this->rec))
      unset($this->rec->$name);
  }
  
  /** We just return a JSON encoding of the attributes in the model when we
    * try to get a string for the model. */
  public function __toString()
  {
    return json_encode((array)$this->rec);
  }

  abstract public function store();
  
  /** This is an alias of store() */
  public function save()
  {
    return $this->store();
  }
  
  abstract public function destroy();

  /** This is an alias of destroy() */
  public function delete()
  {
    return $this->destroy();
  }

  // If this function exists it will override the default behavior of looking in the rec object
  protected function magic_method_handler_exists($name) {
    return in_array("mgm_{$name}", get_class_methods($this));
  }

  protected function call_magic_method_handler($mgm, $name, $value='') {
    return call_user_func_array( array($this, "mgm_{$name}"), array( $mgm, $value ) );
  }
}
