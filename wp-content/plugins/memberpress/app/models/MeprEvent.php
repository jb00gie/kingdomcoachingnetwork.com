<?php
if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}

class MeprEvent extends MeprBaseModel {

  // Supported event types
  public static $users_str = 'users';

  // User events
  public static $login_event_str = 'login';

  public function __construct($id = null)
  {
    if(!is_null($id))
      $this->rec = (object)self::get_one($id);
    else
      $this->rec = (object)array( "id" => 0,
                                  "ip" => null,
                                  "event"  => 'login',
                                  "evt_id" => 0,
                                  "evt_id_type" => 'users',
                                  "created_at"  => null );
  }

  public static function get_one($id, $return_type = OBJECT)
  {
    $mepr_db = new MeprDb();
    $args = compact('id');
    return $mepr_db->get_one_record($mepr_db->events, $args, $return_type);
  }
  
  public static function get_count()
  {
    $mepr_db = new MeprDb();
    return $mepr_db->get_count($mepr_db->events);
  }
  
  public static function get_count_by_event($event)
  {
    $mepr_db = new MeprDb();
    return $mepr_db->get_count($mepr_db->events, compact('event'));
  }
  
  public static function get_count_by_evt_id_type($evt_id_type)
  {
    $mepr_db = new MeprDb();
    return $mepr_db->get_count($mepr_db->events, compact('evt_id_type'));
  }
  
  public static function get_all($order_by = '', $limit = '')
  {
    $mepr_db = new MeprDb();
    return $mepr_db->get_records($mepr_db->events, array(), $order_by, $limit);
  }
  
  public static function get_all_by_event($event, $order_by = '', $limit = '')
  {
    $mepr_db = new MeprDb();
    $args = array('event' => $event);
    return $mepr_db->get_records($mepr_db->events, $args, $order_by, $limit);
  }

  public static function get_all_by_evt_id_type($evt_id_type, $order_by = '', $limit = '')
  {
    $mepr_db = new MeprDb();
    $args = array('evt_id_type' => $evt_id_type);
    return $mepr_db->get_records($mepr_db->events, $args, $order_by, $limit);
  }

  public function store() {
    $mepr_db = new MeprDb();
    do_action('mepr-event-pre-store', $this);
    $vals = (array)$this->rec;
    unset($vals['created_at']); // let mepr_db handle this

    if(isset($this->id) and !is_null($this->id) and (int)$this->id > 0) {
      $mepr_db->update_record( $mepr_db->events, $this->id, $vals );
      do_action('mepr-event-update', $this);
    }
    else {
      $vals['ip'] = ( empty($vals['ip']) ? $_SERVER['REMOTE_ADDR'] : $vals['ip'] );
      $this->id = $mepr_db->create_record( $mepr_db->events, $vals );
      do_action('mepr-event-create', $this);
    }

    do_action('mepr-event-store', $this);

    return $this->id;
  }

  public function destroy()
  {
    $mepr_db = new MeprDb();
    $id = $this->id;
    $args = compact('id');
    $event = self::get_one($id);
    return apply_filters('mepr-event-destroy', $mepr_db->delete_records($mepr_db->events, $args), $args);
  }
} //End class

