<?php
  namespace ForumLib\Utilities;

  use ForumLib\Database\DBUtil;
  use ForumLib\Forums\Category;
  use ForumLib\Forums\Topic;
  use ForumLib\Forums\Thread;

  use ForumLib\Users\User;

  // MISC class - a collection of miscellaneous useful methods.
  class MISC {

    // Finds a file, by default it will try up to 3 parent folders.
    public static function findFile($file, $loops = 3) {
      // Checks whether or not $file exists.
      if(!file_exists($file)) {
        // How many parent folders it'll check. (3 by default)
  			for($i = 0; $i < $loops; $i++) {
  				if(!file_exists($file)) {
  					$file = '../' . $file;
  				}
  			}
  		}
  		return $file;
    }

    // Finds a key within an array. Which means you won't have to know where
    // in the array the key is, just that it exists in there somewhere.
      public static function findKey($aKey, $array) {
          // Check if an array is provided.
          if(is_array($array)) {
              // Loops through the array.
              foreach($array as $key => $item) {
                  // Checks if it did find the matching key. If it doesn't, it continues looping until it does,
                  // or until the end of the array.
                  if($key == $aKey) {
                      return $item;
                  } else {
                      $result = self::findKey($aKey, $item);
                      if($result != false) {
                          return $result;
                      }
                  }
              }
          }
          return false;
      }

      /**
       * @param string $_file - Filename
       * @param DBUtil $SQL
       *
       * @return string
       */
    public static function getPageName($_file, DBUtil $SQL) {
        $page = ucfirst(basename($_file, '.php'));

        if(isset($_GET['page'])) {
            $page = ucfirst($_GET['page']);
        }

        $cat = $top = $trd = null;

        if(isset($_GET['username'])) {
            $U = new User($SQL);
            $user = $U->getUser(str_replace('_', ' ', $_GET['username']), false);

            if(empty($user->username)) {
                $page = 'Profile Not Found';
            } else {
                $page = $user->username . '\'s Profile';
            }
        }

        if(isset($_GET['category'])) {
            $C = new Category($SQL);
            $cat = $C->getCategory($_GET['category'], false);

            $page = $cat->title;
        }

        if(isset($_GET['topic'])) {
            if($cat instanceof Category) {
                $T = new Topic($SQL);
                $top = $T->getTopic($_GET['topic'], false, $cat->id);
            }
            $page = $top->title;
        }

        if(isset($_GET['thread'])) {
            if($top instanceof Topic) {
                $Tr = new Thread($SQL);
                $trd = $Tr->getThread($_GET['thread'], false, $top->id);
            }
            $page = $trd->title;
        }

        return $page;
    }

    public static function parseDate($dateString, Config $config = null, $options = array()) {
        if($config instanceof Config) {
            $format = $config->getConfigValue('timeFormat');
        }

        if(empty($format)) {
            $format = 'F jS Y';
        }

        if(isset($options['howLongAgo'])) {
            $time   = strtotime($dateString);
            $when   = date($format, $time);

            if((time() - $time) < 60) {
                $newTime = time() - $time;
                $when = $newTime . ' ' . ($newTime == 1 ? 'second' : 'seconds') . ' ago';
            }

            if((time() - $time) >= 60 && ((time() - $time) / 60) <= 59) {
                $newTime = round((time() - $time) / 60, 0);
                $when = $newTime . ' ' . ($newTime == 1 ? 'minute' : 'minutes') . ' ago';
            }

            if(((time() - $time) / 60) >= 60 && ((time() - $time) / 60 / 60) <= 23) {
                $newTime = round((time() - $time) / 60 / 60, 0);
                $when = $newTime . ' ' . ($newTime == 1 ? 'hour' : 'hours') . ' ago';
            }

            return $when;
        } else {
            return date($format, strtotime($dateString));
        }
    }

    public static function array_sort_key() {
        $args = func_get_args();
        $data = array_shift($args);

        foreach ($args as $n => $field) {
            if (is_string($field)) {
                $tmp = array();
                foreach ($data as $key => $row) {
                    $tmp[$key] = $row[$field];
                    $args[$n] = $tmp;
                }
            }
        }

        $args[] = &$data;
        call_user_func_array('array_multisort', $args);

        return array_pop($args);
    }
  }
