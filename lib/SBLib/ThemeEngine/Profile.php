<?php
    namespace SBLib\ThemeEngine;

    use SBLib\Users\User;
    use SBLib\Users\Group;

    use SBLib\Forums\Post;

    use SBLib\Utilities\MISC;

    class Profile extends MainEngine {

        private $engine;

        public function __construct(MainEngine $_engine) {
            parent::__construct($_engine->_SQL);
            $this->engine = $_engine;
        }

        public function parseGroup($_template, Group $_group) {
            $matches = $this->engine->findPlaceholders($_template);

            foreach($matches as $match) {
                /** @var Placeholder $match */
                switch($match->getOption()) {
                    case 'id':
                    case 'gid':
                        $_template = $this->engine->replaceVariable($match->getPlaceholder(), $_template, $_group->id);
                        break;
                    case 'name':
                        $_template = $this->engine->replaceVariable($match->getPlaceholder(), $_template, $_group->name);
                        break;
                }
            }

            return $_template;
        }

        public function parseProfile($_template, User $_user) {
            $matches = $this->engine->findPlaceholders($_template);

            foreach($matches as $match) {
                /** @var Placeholder $match */
                switch($match->getOption()) {
                    case 'id':
                        $_template = $this->engine->replaceVariable($match->getPlaceholder(), $_template, $_user->id, __FILE__, __LINE__);
                        break;
                    case 'username':
                        $_template = $this->engine->replaceVariable($match->getPlaceholder(), $_template, $_user->username, __FILE__, __LINE__);
                        break;
                    case 'email':
                        $_template = $this->engine->replaceVariable($match->getPlaceholder(), $_template, $_user->email, __FILE__, __LINE__);
                        break;
                    case 'profileUrl':
                        $_template = $this->engine->replaceVariable($match->getPlaceholder(), $_template, $_user->getURL(), __FILE__, __LINE__);
                        break;
                    case 'avatar':
                        $_template = $this->engine->replaceVariable($match->getPlaceholder(), $_template, $_user->avatar, __FILE__, __LINE__);
                        break;
                    case 'about':
                        $_template = $this->engine->replaceVariable($match->getPlaceholder(), $_template, (empty($_user->about) ? 'This user hasn\'t said anything about themselves.' : $_user->about), __FILE__, __LINE__);
                        break;
                    case 'lastVisit':
                        $date = MISC::parseDate($_user->lastLogin, $this->engine->_Config, array('howLongAgo' => true));
                        $_template = $this->engine->replaceVariable($match->getPlaceholder(), $_template, $date, __FILE__, __LINE__);
                        break;
                    case 'joined':
                        $date = MISC::parseDate($_user->regDate, $this->engine->_Config, array('howLongAgo' => true));
                        $_template = $this->engine->replaceVariable($match->getPlaceholder(), $_template, $date, __FILE__, __LINE__);
                        break;
                    case 'location':
                        $location = ($_user->location ? $_user->location : 'Unknown');
                        $_template = $this->engine->replaceVariable($match->getPlaceholder(), $_template, $location, __FILE__, __LINE__);
                        break;
                    case 'website':
                        // TODO: Add functionality.
                        $_template = $this->engine->replaceVariable($match->getPlaceholder(), $_template, 'Unknown', __FILE__, __LINE__);
                        break;
                    case 'hasWebsite':
                        // TODO: Add functionality.
                        $_template = $this->engine->replaceVariable($match->getPlaceholder(), $_template, '-broken', __FILE__, __LINE__);
                        break;
                    case 'latestPosts':
                        /*$F = new Forums($this->engine);

                        $_user->setSQL($this->engine->_SQL);
                        $posts = $_user->getLatestPosts();

                        $html = '';

                        if(count($posts) == 0) {
                            $html = $this->engine->getTemplate('no_profile_posts', 'user');
                        } else {
                            $amount = ($template[2] ? $template[2] : 5);
                            $amount = ($amount > count($posts) ? count($posts) : $amount);

                            for($i = 0; $i < $amount; $i++) {
                                if($posts[$i]['post'] instanceof Post) {
                                    $html .= $F->parseThread($F->parsePost($this->engine->getTemplate('profile_post', 'user'), $posts[$i]['post']), $posts[$i]['thread']);
                                }
                            }
                        }

                        $_template = $this->engine->replaceVariable($match, $_template, $html, __FILE__, __LINE__);*/
                        break;
                    case 'groupName':
                        $_template = $this->engine->replaceVariable($match->getPlaceholder(), $_template, (isset($_user->group->name) ? $_user->group->name : 'Unknown'), __FILE__, __LINE__);
                        break;
                    case 'postCount':
                        $_template = $this->engine->replaceVariable($match->getPlaceholder(), $_template, $_user->getPostCount(), __FILE__, __LINE__);
                        break;
                }
            }

            return $_template;
        }
    }