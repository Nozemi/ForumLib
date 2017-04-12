<?php
  namespace ForumLib\Utilities;

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
						$result = self::FindKey($aKey, $item);
						if($result != false) {
							return $result;
						}
					}
				}
			}
			return false;
    }

      /**
       * @param $_file string - Filename
       *
       * @return string
       */
    public static function getTabTitle($_file) {
        global $Config;

        if(!$Config instanceof Config) {
            $Config = new Config;
        }

        $title = $_file;

        $title = MISC::findKey('name', $Config->config) . ' - ' . ucfirst(basename($title, '.php'));

        if(isset($_GET['page'])) {
            $title = MISC::findKey('name', $Config->config) . ' - ' . ucfirst($_GET['page']);
        }

        return $title;
    }
  }
