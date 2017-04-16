<?php
    namespace ForumLib\ThemeEngine;

    use ForumLib\Users\User;
    use ForumLib\Utilities\MISC;

    class Profile extends ThemeEngine {

        private $engine;

        public function __construct(ThemeEngine $_engine) {
            if($_engine instanceof ThemeEngine) {
                $this->engine = $_engine;
            }
        }

        public function parseProfile($_template, User $_user) {
            $matches = $this->engine->getPlaceholders($_template);

            foreach($matches[1] as $match) {
                $template = explode('::', $match);

                switch($template[1]) {
                    case 'username':
                        $_template = $this->engine->replaceVariable($match, $_template, $_user->username);
                        break;
                    case 'avatar':
                        $_template = $this->engine->replaceVariable($match, $_template, $_user->avatar);
                        break;
                    case 'about':
                        $_template = $this->engine->replaceVariable($match, $_template, (empty($_user->about) ? 'This user hasn\'t said anything about themselves.' : $_user->about));
                        break;
                    case 'joined':
                        $date = MISC::parseDate($_user->regDate, $this->config, array('howLongAgo' => true));
                        $_template = $this->engine->replaceVariable($match, $_template, $date);
                        break;
                    case 'location':
                        $location = ($_user->location ? $_user->location : 'Unknown');
                        $_template = $this->engine->replaceVariable($match, $_template, $location);
                        break;
                    case 'website':
                        // TODO: Add functionality.
                        $_template = $this->engine->replaceVariable($match, $_template, 'Unknown');
                        break;
                    case 'hasWebsite':
                        // TODO: Add functionality.
                        $_template = $this->engine->replaceVariable($match, $_template, '-broken');
                        break;
                    case 'latestPosts':
                        $F = new Forums($this->engine);

                        $_user->setSQL($this->engine->sql);
                        $posts = $_user->getLatestPosts();

                        $html = '';

                        if(count($posts) == 0) {
                            $html = $this->engine->getTemplate('no_profile_posts', 'user');
                        } else {
                            $amount = ($template[2] ? $template[2] : 5);
                            $amount = ($amount > count($posts) ? count($posts) : $amount);

                            for($i = 0; $i < $amount; $i++) {
                                $html .= $F->parseThread($F->parsePost($this->engine->getTemplate('profile_post', 'user'), $posts[$i]['post']), $posts[$i]['thread']);
                            }
                        }

                        $_template = $this->engine->replaceVariable($match, $_template, $html);
                        break;
                }
            }

            return $_template;
        }
    }