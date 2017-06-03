<?php
    namespace ForumLib\ThemeEngine;

    use ForumLib\Forums\Category;
    use ForumLib\Forums\Topic;
    use ForumLib\Forums\Thread;
    use ForumLib\Forums\Post;

    use ForumLib\Utilities\MISC;

    use ForumLib\Users\User;
    use ForumLib\Users\Permissions;

    class Forums extends MainEngine {
        protected $engine;

        public function __construct(MainEngine $_engine) {
            if($_engine instanceof MainEngine) {
                $this->engine = $_engine;
            } else {
                $this->__destruct();
            }
        }

        public function __destruct() {
            $this->engine = null;
        }

        public function parseForum($_template, $_fObject) {
<<<<<<< HEAD
            $matches = $this->engine->findPlaceholders($_template);

            if($_fObject instanceof Category) {
                $_template = $this->parseCategory($_template, $_fObject);
            }

            if($_fObject instanceof Topic) {
                $_fObject->setThreadCount()
                    ->setPostCount();

=======
            if($_fObject instanceof Category) {
                $_template = $this->parseCategory($_template, $_fObject);
            }

            if($_fObject instanceof Topic) {
                $_fObject->setThreadCount()
                    ->setPostCount();

>>>>>>> 615a34eea3757a7329b41b8f2d8bd5f54f42e90f
                $_template = $this->parseTopic($_template, $_fObject);
            }

            if($_GET['page'] == 'portal' && $_fObject instanceof Thread) {
                $P = new Post($this->engine->_SQL);
                $posts = $P->getPosts($_fObject->id);
                $_template = $this->parseThread($this->parsePost($_template, $posts[0]), $_fObject);
            }

            if($_fObject instanceof Thread) {
                $_fObject->setLatestPost();
                $_fObject->setPosts();

                $_template = $this->parseThread($_template, $_fObject);
            }

            if($_fObject instanceof Post) {
                $_template = $this->parsePost($_template, $_fObject);
            }

            return $_template;
        }

        public function parseCategory($_template, Category $_category) {
            $matches = $this->engine->findPlaceholders($_template);

            foreach($matches[1] as $match) {
                $template = explode('::', $match);

                switch($template[1]) {
                    case 'id':
                    case 'cid':
                        $_template = $this->engine->replaceVariable($match, $_template, $_category->id);
                        break;
                    case 'safeDesc':
                    case 'safeDescription':
                        $_template = $this->engine->replaceVariable($match, $_template, str_replace('\'', "\'", $_category->description));
                        break;
                    case 'safeName':
                        $_template = $this->engine->replaceVariable($match, $_template, str_replace('\'', "\'", $_category->title));
                        break;
                    case 'order':
                        $_template = $this->engine->replaceVariable($match, $_template, $_category->order);
                        break;
                    case 'header':
                    case 'title':
                        $_template = $this->engine->replaceVariable($match, $_template, $_category->title);
                        break;
                    case 'description':
                        $_template = $this->engine->replaceVariable($match, $_template, $_category->description);
                        break;
                    case 'topics':
                        $html = '';
                        $T = new Topic($this->engine->_SQL);
                        $tops = $T->getTopics($_category->id);
                        foreach($tops as $top) {
                            $html .= $this->parseForum($this->engine->getTemplate('topic_view', 'forums'), $top);
                        }
                        $_template = $this->engine->replaceVariable($match, $_template, $html);
                        break;
                    case 'adminMenu':
                        $html = '';

                        if(!empty($_SESSION['user'])) {
                            $U = new User($this->engine->_SQL);
                            $user = $U->getUser($_SESSION['user']['id']);

                            if($user->group->admin) {
                                $html = $this->parseCategory($this->engine->getTemplate('admin_categories', 'admin'), $_category);
                            }
                        }

                        $_template = $this->engine->replaceVariable($match, $_template, $html);
                        break;
                }
            }

            return $_template;
        }

        public function parseTopic($_template, Topic $_topic) {
            $matches = $this->engine->findPlaceholders($_template);

            $C = new Category($this->engine->_SQL);
            $cat = $C->getCategory($_topic->categoryId);

            $latest = $_topic->getLatestPost();

            foreach($matches[1] as $match) {
                $template = explode('::', $match);

                switch($template[1]) {
                    case 'id':
                        $_template = $this->engine->replaceVariable($match, $_template, $_topic->id);
                    case 'header':
                    case 'title':
                        $_template = $this->engine->replaceVariable($match, $_template, $_topic->title);
                        break;
                    case 'description':
                        $_template = $this->engine->replaceVariable($match, $_template, $_topic->description);
                        break;
                    case 'order':
                        $_template = $this->engine->replaceVariable($match, $_template, $_topic->order);
                        break;
                    case 'url':
                        $_template = $this->engine->replaceVariable($match, $_template,
                                                                    '/forums/' . $cat->getURL() . '/' . $_topic->getURL()
                        );
                        break;
                    case 'threadCount':
                        $count = $_topic->threadCount . ($_topic->threadCount == 1 ? ' Thread' : ' Threads');
                        $_template = $this->engine->replaceVariable($match, $_template, $count);
                        break;
                    case 'postCount':
                        $count = max(($_topic->postCount - $_topic->threadCount), 0);
                        if($template[2] == 'threadCount') { $count += max(($_topic->threadCount), 0); }
                        $_template = $this->engine->replaceVariable($match, $_template, $count . (($_topic->postCount - $_topic->threadCount) == 1 ? ' Post' : ' Posts'));
                        break;
                    case 'lastThreadTitle':
                        $title = ($latest['thread']->title ? $latest['thread']->title : 'No posts yet');
                        $_template = $this->engine->replaceVariable($match, $_template, $title);
                        break;
                    case 'lastThreadUrl':
                        $url = '#';

                        if($latest['thread'] instanceof Thread && $cat instanceof Category) {
                            $T = new Topic($this->engine->_SQL);
                            $tpc = $T->getTopic($latest['thread']->topicId);

                            if($tpc instanceof Topic) {
                                $url = '/forums/' . $cat->getURL() . '/' . $tpc->getURL() . '/' . $latest['thread']->getURL();
                            }
                        }

                        $_template = $this->engine->replaceVariable($match, $_template, $url);
                        break;
                    case 'lastPoster':
                        $username = ($latest['post']->author->username ? $latest['post']->author->username : 'N/A');
                        $_template = $this->engine->replaceVariable($match, $_template, $username);
                        break;
                    case 'lastPosterAvatar':
                        if(!empty($latest['post']->author->avatar)) {
                            $avatar = ($latest['post']->author->avatar ? $latest['post']->author->avatar : '/' . $this->engine->directory . '/_assets/img/user/avatar.jpg');
                        } else {
                            $avatar = '/' . $this->engine->directory . '/_assets/img/' . $template[2];
                        }
                        $_template = $this->engine->replaceVariable($match, $_template, $avatar);
                        break;
                    case 'lastPosterUrl':
                        $url = ($latest['post']->author->username ? '/profile/' . $latest['post']->author->username : '#');
                        $_template = $this->engine->replaceVariable($match, $_template, $url);
                        break;
                    case 'lastPostDate':
                        $date = ($latest['post']->post_date ? MISC::parseDate($latest['post']->post_date, $this->engine->_Config, array('howLongAgo' => true)) : 'No posts...');
                        $_template = $this->engine->replaceVariable($match, $_template, $date);
                        break;
                    case 'threads':
                        $html = '';

                        $_topic->setThreads();

                        if(!empty($_topic->threads)) {
                            foreach($_topic->threads as $thread) {
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
                    case 'newThreadUrl':
                        $url = '/newthread/' . $cat->getURL() . '/' . $_topic->getURL();
                        $_template = $this->engine->replaceVariable($match, $_template, $url);
                        break;
                    case 'adminMenu':
                        $html = '';

                        if(!empty($_SESSION['user'])) {
                            $U = new User($this->engine->_SQL);
                            $user = $U->getUser($_SESSION['user']['id']);

                            if($user->group->admin) {
                                $html = $this->engine->getTemplate('admin_topic', 'admin');
                            }
                        }

                        $_template = $this->engine->replaceVariable($match, $_template, $html);
                        break;
                }
            }

            return $_template;
        }

        public function parseThread($_template, Thread $_thread) {
            $matches = $this->engine->findPlaceholders($_template);

            foreach($matches[1] as $match) {
                $template = explode('::', $match);

                switch($template[1]) {
                    case 'title':
                        $_template = $this->engine->replaceVariable($match, $_template, $_thread->title);
                        break;
                    case 'posts':
                        $html = '';

                        foreach($_thread->posts as $post) {
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
                        $_template = $this->engine->replaceVariable($match, $_template, $_thread->id);
                        break;
                    case 'lastResponderAvatar':
                        $avatar = $_thread->author->avatar;
                        $_template = $this->engine->replaceVariable($match, $_template, $avatar);
                        break;
                    case 'poster':
                        if($_thread->author instanceof User) {
                            $_template = $this->engine->replaceVariable($match, $_template, $_thread->author->username);
                        }
                        break;
                    case 'lastReplyDate':
                        $date = MISC::parseDate($_thread->latestPost->post_date, $this->engine->_Config, array('howLongAgo' => true));
                        $_template = $this->engine->replaceVariable($match, $_template, $date);
                        break;
                    case 'postDate':
                        $date = MISC::parseDate($_thread->posted, $this->engine->_Config, array('howLongAgo' => true));
                        $_template = $this->engine->replaceVariable($match, $_template, $date);
                        break;
                    case 'viewCount':
                        // TODO: Add functionality.
                        $_template = $this->engine->replaceVariable($match, $_template, '0 Views');
                        break;
                    case 'replyCount':
                        $count = (count($_thread->posts) - 1) . ((count($_thread->posts) - 1) == 1 ? ' Reply' : ' Replies');
                        $_template = $this->engine->replaceVariable($match, $_template, $count);
                        break;
                    case 'lastResponder':
                        $username = ($_thread->latestPost->author->username ? $_thread->latestPost->author->username : 'Unknown');
                        $_template = $this->engine->replaceVariable($match, $_template, $username);
                        break;
                    case 'url':
                        $T = new Topic($this->engine->_SQL);
                        $top = $T->getTopic($_thread->topicId);

                        if($top instanceof Topic) {
                            $C = new Category($this->engine->_SQL);
                            $cat = $C->getCategory($top->categoryId);

                            $_template = $this->engine->replaceVariable($match, $_template,
                                                                        '/forums/' . $cat->getURL() . '/' . $top->getURL() . '/' . $_thread->getURL());
                        }
                        break;
                    case 'latestPostDate':
                        $date = MISC::parseDate($_thread->latestPost->post_date, $this->engine->_Config, array('howLongAgo' => true));
                        $_template = $this->engine->replaceVariable($match, $_template, $date);
                        break;
                    case 'lastPosterAvatar':
                        $_template = $this->engine->replaceVariable($match, $_template, $_thread->latestPost->author->avatar);
                        break;
                    case 'lastPosterUrl':
                        $_template = $this->engine->replaceVariable($match, $_template, '/profile/' . $_thread->latestPost->author->username);
                        break;
                }
            }

            return $_template;
        }

        public function parsePost($_template, Post $_post) {
            $matches = $this->engine->findPlaceholders($_template);

            foreach($matches[1] as $match) {
                $template = explode('::', $match);

                switch($template[1]) {
                    case 'id':
                    case 'pid':
                        $_template = $this->engine->replaceVariable($match, $_template, $_post->id);
                        break;
                    case 'poster':
                        if($_post->author instanceof User) {
                            $_template = $this->engine->replaceVariable($match, $_template, $_post->author->username);
                        }
                        break;
                    case 'posterStatus':
                        $U = new User($this->engine->_SQL);

                        $status = ($U->getStatus($_post->author->id) ? 'success' : 'danger');
                        $_template = $this->engine->replaceVariable($match, $_template, $status);
                        break;
                    case 'posterStatus':
                        $U = new User($this->engine->_SQL);

                        $status = ($U->getStatus($_post->author->id) ? 'success' : 'danger');
                        $_template = $this->engine->replaceVariable($match, $_template, $status);
                        break;
                    case 'posterAvatar':
                        if($_post->author instanceof User) {
                            $_template = $this->engine->replaceVariable($match, $_template, $_post->author->avatar);
                        }
                        break;
                    case 'posterMemberSince':
                        $date = MISC::parseDate($_post->author->regDate, $this->engine->_Config, array('howLongAgo' => true));
                        $_template = $this->engine->replaceVariable($match, $_template, $date);
                        break;
                    case 'content':
                        $content = (isset($_post->post_html) ? $_post->post_html : '<p>' . $_post->post_text . '</p>');
                        $_template = $this->engine->replaceVariable($match, $_template, $content);
                        break;
                    case 'posted':
                        $date = MISC::parseDate($_post->post_date, $this->engine->_Config, array('howLongAgo' => true));
                        $_template = $this->engine->replaceVariable($match, $_template, $date);
                        break;
                    case 'threadTitle':
                        $T = new Thread($this->engine->_SQL);
                        $trd = $T->getThread($_post->threadId);
                        $_template = $this->engine->replaceVariable($match, $_template, $trd->title);
                        break;
                    case 'posterUrl':
                        if($_post->author instanceof User) {
                            $_template = $this->engine->replaceVariable($match, $_template, '/profile/' . $_post->author->username);
                        }
                        break;
                    case 'manage':
                        $html = '';

                        if(!empty($_SESSION['user'])) {
                            $U = new User($this->engine->_SQL);
                            $user = $U->getUser($_SESSION['user']['id']);

                            if($_SESSION['user']['id'] == $_post->author->id
                            || $user->group->admin) {
                                $html = $this->parsePost($this->engine->getTemplate('post_view_manage', 'forums'), $_post);
                            }
                        }

                        $_template = $this->engine->replaceVariable($match, $_template, $html);
                        break;
                    case 'originalPost':
                        $html = '';

                        if($_post->originalPost) {
                            $html = 'originalPost';
                        }

                        $_template = $this->engine->replaceVariable($match, $_template, $html);
                        break;
                }
            }

            return $_template;
        }
    }