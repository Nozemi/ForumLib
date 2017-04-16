<?php
    namespace ForumLib\ThemeEngine;

    use ForumLib\Forums\Category;
    use ForumLib\Forums\Topic;
    use ForumLib\Forums\Thread;
    use ForumLib\Forums\Post;

    use ForumLib\Utilities\MISC;

    class Forums extends ThemeEngine {
        protected $engine;

        public function __construct(ThemeEngine $_engine) {
            if($_engine instanceof ThemeEngine) {
                $this->engine = $_engine;
            } else {
                $this->__destruct();
            }
        }

        public function __destruct() {
            $this->engine = null;
        }

        public function parseForum($_template, $_fObject) {
            preg_match_all('/' . $this->engine->pHolderWrapper[0] . '(.*?)' . $this->engine->pHolderWrapper[1] . '/', $_template, $matches);

            foreach($matches[1] as $match) {
                $template = explode('::', $match);

                switch($template[0]) {
                    case 'category':
                        switch($template[1]) {
                            case 'header':
                                $_template = $this->engine->replaceVariable($match, $_template, $_fObject->title);
                                break;
                            case 'description':
                                $_template = $this->engine->replaceVariable($match, $_template, $_fObject->description);
                                break;
                            case 'topics':
                                $html = '';
                                $T = new Topic($this->engine->sql);
                                $tops = $T->getTopics($_fObject->id);
                                foreach($tops as $top) {
                                    $html .= $this->parseForum($this->engine->getTemplate('topic_view', 'forums'), $top);
                                }
                                $_template = $this->engine->replaceVariable($match, $_template, $html);
                                break;
                        }
                        break;
                    case 'topic':
                        /** @var $_fObject Topic */

                        $C = new Category($this->engine->sql);
                        $cat = $C->getCategory($_fObject->categoryId);

                        $latest = $_fObject->getLatestPost();

                        $_fObject->setThreadCount()
                            ->setPostCount();

                        switch($template[1]) {
                            case 'header':
                                $_template = $this->engine->replaceVariable($match, $_template, $_fObject->title);
                                break;
                            case 'description':
                                $_template = $this->engine->replaceVariable($match, $_template, $_fObject->description);
                                break;
                            case 'url':
                                $_template = $this->engine->replaceVariable($match, $_template,
                                                                    '/forums/' . $cat->getURL() . '/' . $_fObject->getURL()
                                );
                                break;
                            case 'threadCount':
                                $count = $_fObject->threadCount . ($_fObject->threadCount == 1 ? ' Thread' : ' Threads');
                                $_template = $this->engine->replaceVariable($match, $_template, $count);
                                break;
                            case 'postCount':
                                $count = max(($_fObject->postCount - 1), 0) . (($_fObject->postCount - 1) == 1 ? ' Post' : ' Posts');
                                $_template = $this->engine->replaceVariable($match, $_template, $count);
                                break;
                            case 'lastThreadTitle':
                                $title = ($latest['thread']->title ? $latest['thread']->title : 'No posts yet');
                                $_template = $this->engine->replaceVariable($match, $_template, $title);
                                break;
                            case 'lastThreadUrl':
                                $url = '#';

                                if($latest['thread'] instanceof Thread) {
                                    $T = new Topic($this->engine->sql);
                                    $tpc = $T->getTopic($latest['thread']->id);
                                    $url = '/forums/' . $cat->getURL() . '/' . $tpc->getURL() . '/' . $latest['thread']->getURL();
                                }

                                $_template = $this->engine->replaceVariable($match, $_template, $url);
                                break;
                            case 'lastPoster':
                                $username = ($latest['post']->author->username ? $latest['post']->author->username : 'N/A');
                                $_template = $this->engine->replaceVariable($match, $_template, $username);
                                break;
                            case 'lastPosterAvatar':
                                $avatar = ($latest['post']->author->avatar ? $latest['post']->author->avatar : '/' . $this->engine->directory . '/_assets/img/user/avatar.jpg');
                                $_template = $this->engine->replaceVariable($match, $_template, $avatar);
                                break;
                            case 'lastPosterUrl':
                                $url = ($latest['post']->author->username ? '/profile/' . $latest['post']->author->username : '#');
                                $_template = $this->engine->replaceVariable($match, $_template, $url);
                                break;
                        }
                        break;
                    case 'thread':
                        /** @var $_fObject Thread */
                        if($_fObject instanceof Thread) {
                            $_fObject->setLatestPost();
                            $_fObject->setPosts();
                        }
                        switch($template[1]) {
                            case 'title':
                                $_template = $this->engine->replaceVariable($match, $_template, $_fObject->title);
                                break;
                            case 'lastResponderAvatar':
                                $avatar = $_fObject->author->avatar;
                                $_template = $this->engine->replaceVariable($match, $_template, $avatar);
                                break;
                            case 'poster':
                                $_template = $this->engine->replaceVariable($match, $_template, $_fObject->author->username);
                                break;
                            case 'lastReplyDate':
                                $date = MISC::parseDate($_fObject->latestPost->post_date, $this->engine->config, array('howLongAgo' => true));
                                $_template = $this->engine->replaceVariable($match, $_template, $date);
                                break;
                            case 'postDate':
                                $date = MISC::parseDate($_fObject->posted, $this->engine->config, array('howLongAgo' => true));
                                $_template = $this->engine->replaceVariable($match, $_template, $date);
                                break;
                            case 'viewCount':
                                // TODO: Add functionality.
                                $_template = $this->engine->replaceVariable($match, $_template, '0 Views');
                                break;
                            case 'replyCount':
                                $count = (count($_fObject->posts) - 1) . ((count($_fObject->posts) - 1) == 1 ? ' Reply' : ' Replies');
                                $_template = $this->engine->replaceVariable($match, $_template, $count);
                                break;
                            case 'lastResponder':
                                $username = ($_fObject->latestPost->author->username ? $_fObject->latestPost->author->username : 'Unknown');
                                $_template = $this->engine->replaceVariable($match, $_template, $username);
                                break;
                            case 'url':
                                $top = new Topic($this->engine->sql);
                                $top = $top->getTopic($_fObject->topicId);

                                $cat = new Category($this->engine->sql);
                                $cat = $cat->getCategory($top->categoryId);

                                $_template = $this->engine->replaceVariable($match, $_template,
                                                                    '/forums/' . $cat->getURL() . '/' . $top->getURL() . '/' . $_fObject->getURL());
                                break;
                            case 'latestPostDate':
                                $date = MISC::parseDate($_fObject->latestPost->post_date, $this->engine->config, array('howLongAgo' => true));
                                $_template = $this->engine->replaceVariable($match, $_template, $date);
                                break;
                            case 'lastPosterAvatar':
                                $_template = $this->engine->replaceVariable($match, $_template, $_fObject->latestPost->author->avatar);
                                break;
                            case 'lastPosterUrl':
                                $_template = $this->engine->replaceVariable($match, $_template, '/profile/' . $_fObject->latestPost->author->username);
                                break;
                        }
                        break;
                    case 'post':
                        /** @var $_fObject Post */
                        if($_fObject instanceof Post) {
                            switch($template[1]) {
                                case 'poster':
                                    $_template = $this->engine->replaceVariable($match, $_template, $_fObject->author->username);
                                    break;
                                case 'posterAvatar':
                                    $_template = $this->engine->replaceVariable($match, $_template, $_fObject->author->avatar);
                                    break;
                                case 'posterMemberSince':
                                    $date = MISC::parseDate($_fObject->author->regDate, $this->engine->config, array('howLongAgo' => true));
                                    $_template = $this->engine->replaceVariable($match, $_template, $date);
                                    break;
                                case 'content':
                                    $content = (isset($_fObject->post_html) ? $_fObject->post_html : '<p>' . $_fObject->post_text . '</p>');
                                    $_template = $this->engine->replaceVariable($match, $_template, $content);
                                    break;
                                case 'posted':
                                    $date = MISC::parseDate($_fObject->post_date, $this->engine->config, array('howLongAgo' => true));
                                    $_template = $this->engine->replaceVariable($match, $_template, $date);
                                    break;
                                case 'threadTitle':
                                    $T = new Thread($this->engine->sql);
                                    $trd = $T->getThread($_fObject->threadId);
                                    $_template = $this->engine->replaceVariable($match, $_template, $trd->title);
                                    break;
                                case 'posterUrl':
                                    $_template = $this->engine->replaceVariable($match, $_template, '/profile/' . $_fObject->author->username);
                                    break;
                            }
                        }
                        break;
                    case 'threadList':
                        switch($template[1]) {
                            case 'header':
                                $_template = $this->engine->replaceVariable($match, $_template, $_fObject->title);
                                break;
                            case 'description':
                                $_template = $this->engine->replaceVariable($match, $_template, $_fObject->description);
                                break;
                            case 'threads':
                                $html = '';

                                /** @var $_fObject Topic */
                                $_fObject->setThreads();

                                if(!empty($_fObject->threads)) {
                                    foreach($_fObject->threads as $thread) {
                                        $html .= $this->parseForum($this->engine->getTemplate('thread_view', 'forums'), $thread);
                                    }
                                } else {
                                    $html = $this->engine->getTemplate('no_threads_msg', 'misc');
                                }

                                $_template = $this->engine->replaceVariable($match, $_template, $html);
                                break;
                            case 'moderate':
                                $html = '';
                                if(!empty($_SESSION['user'])) {
                                    $html = $this->engine->getTemplate('topic_view_moderate', 'forums');
                                }
                                $_template = $this->engine->replaceVariable($match, $_template, $html);
                                break;
                        }
                        break;
                    case 'categoryView':
                        /** @val $_fObject Category */
                        switch($template[1]) {
                            case 'header':
                                $_template = $this->engine->replaceVariable($match, $_template, $_fObject->title);
                                break;
                            case 'description':
                                $_template = $this->engine->replaceVariable($match, $_template, $_fObject->description);
                                break;
                            case 'topics':
                                $T = new Topic($this->engine->sql);
                                $topics = $T->getTopics($_fObject->id);

                                $html = '';

                                foreach($topics as $topic) {
                                    $html .= $this->parseForum($this->engine->getTemplate('topic_view', 'forums'), $topic);
                                }

                                $_template = $this->engine->replaceVariable($match, $_template, $html);
                                break;
                        }
                        break;
                    case 'threadView':
                        /** @val $_fObject Thread */

                        $C = new Category($this->engine->sql);
                        $cat = $C->getCategory($_GET['category'], false);

                        $T = new Topic($this->engine->sql);
                        $top = $T->getTopic($_GET['topic'], false, $cat->id);

                        $TR = new Thread($this->engine->sql);
                        $trd = $TR->getThread($_GET['thread'], false, $top->id);
                        $trd->setPosts();

                        switch($template[1]) {
                            case 'title':
                                $_template = $this->engine->replaceVariable($match, $_template, $trd->title);
                                break;
                            case 'posts':
                                $html = '';

                                foreach($trd->posts as $post) {
                                    $html .= $this->parseForum($this->engine->getTemplate('post_view', 'forums'), $post);
                                }

                                $_template = $this->engine->replaceVariable($match, $_template, $html);
                                break;
                            case 'reply':
                                $html = '';
                                if(!empty($_SESSION['user'])) {
                                    $html = $this->engine->getTemplate('thread_view_reply', 'forums');
                                }
                                $_template = $this->engine->replaceVariable($match, $_template, $html);
                                break;
                            case 'moderate':
                                $html = '';
                                if(!empty($_SESSION['user'])) {
                                    $html = $this->engine->getTemplate('thread_view_moderate', 'forums');
                                }
                                $_template = $this->engine->replaceVariable($match, $_template, $html);
                                break;
                            case 'id':
                                $_template = $this->engine->replaceVariable($match, $_template, $trd->id);
                                break;
                        }
                        break;
                    case 'news':
                        switch($template[1]) {
                            case 'title':
                                $_template = $this->engine->replaceVariable($match, $_template, $_fObject->title);
                                break;
                            case 'author':
                                $_template = $this->engine->replaceVariable($match, $_template, $_fObject->author->username);
                                break;
                            case 'authorUrl':
                                $_template = $this->engine->replaceVariable($match, $_template, '/profile/' . $_fObject->author->username);
                                break;
                            case 'authorAvatar':
                                $_template = $this->engine->replaceVariable($match, $_template, $_fObject->author->avatar);
                                break;
                            case 'posted':
                                $date = MISC::parseDate($_fObject->posted, $this->engine->config, array('howLongAgo' => true));
                                $_template = $this->engine->replaceVariable($match, $_template, $date);
                                break;
                            case 'content':
                                /** @var Post $post */
                                $_fObject->setPosts();
                                $post = $_fObject->posts[0];

                                $content = (!empty($post->post_html) ? $post->post_html : '<p>' . $post->post_text . '</p>');
                                $_template = $this->engine->replaceVariable($match, $_template, $content);
                                break;
                            case 'url':
                                $T = new Topic($this->engine->sql);
                                $top = $T->getTopic($_fObject->topicId);

                                $C = new Category($this->engine->sql);
                                $cat = $C->getCategory($top->categoryId);

                                $url = '/forums/' . $cat->getURL() . '/' . $top->getURL() . '/' . $_fObject->getURL();
                                $_template = $this->engine->replaceVariable($match, $_template, $url);
                                break;
                        }
                        break;
                    default:
                        break;
                }
            }

            return $_template;
        }
    }