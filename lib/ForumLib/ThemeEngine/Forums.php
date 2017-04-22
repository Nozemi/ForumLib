<?php
    namespace ForumLib\ThemeEngine;

    use ForumLib\Forums\Category;
    use ForumLib\Forums\Topic;
    use ForumLib\Forums\Thread;
    use ForumLib\Forums\Post;

    use ForumLib\Utilities\MISC;

    use ForumLib\Users\User;
    use ForumLib\Users\Permissions;

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
            $matches = $this->engine->getPlaceholders($_template);

            foreach($matches[1] as $match) {
                $template = explode('::', $match);

                switch($template[0]) {
                    case 'category':
                    case 'categoryView':
                        if($_fObject instanceof Category) {
                            $_template = $this->parseCategory($_template, $_fObject);
                        }
                        break;
                    case 'topic':
                    case 'threadList':
                        if($_fObject instanceof Topic) {
                            $_fObject->setThreadCount()
                                ->setPostCount();

                            $_template = $this->parseTopic($_template, $_fObject);
                        }
                        break;
                    case 'thread':
                        if($_fObject instanceof Thread) {
                            $_fObject->setLatestPost();
                            $_fObject->setPosts();

                            $_template = $this->parseThread($_template, $_fObject);
                        }
                        break;
                    case 'threadView':
                        $C = new Category($this->engine->sql);
                        $cat = $C->getCategory($_GET['category'], false);

                        $T = new Topic($this->engine->sql);
                        $top = $T->getTopic($_GET['topic'], false, $cat->id);

                        $TR = new Thread($this->engine->sql);
                        if(isset($_GET['threadId'])) {
                            $trd = $TR->getThread($_GET['threadId']);
                        } else {
                            $trd = $TR->getThread($_GET['thread'], false, $top->id);
                        }
                        $trd->setPosts();

                        $_template = $this->parseThread($_template, $trd);
                        break;
                    case 'post':
                        if($_fObject instanceof Post) {
                            $_template = $this->parsePost($_template, $_fObject);
                        }
                        break;
                    case 'news':
                        if($_fObject instanceof Thread) {
                            $P = new Post($this->engine->sql);
                            $posts = $P->getPosts($_fObject->id);
                            $_template = $this->parseThread($this->parsePost($_template, $posts[0]), $_fObject);
                        }
                        break;
                }
            }

            return $_template;
        }

        public function parseCategory($_template, Category $_category) {
            $matches = $this->engine->getPlaceholders($_template);

            foreach($matches[1] as $match) {
                $template = explode('::', $match);

                switch($template[1]) {
                    case 'header':
                    case 'title':
                        $_template = $this->engine->replaceVariable($match, $_template, $_category->title);
                        break;
                    case 'description':
                        $_template = $this->engine->replaceVariable($match, $_template, $_category->description);
                        break;
                    case 'topics':
                        $html = '';
                        $T = new Topic($this->engine->sql);
                        $tops = $T->getTopics($_category->id);
                        foreach($tops as $top) {
                            $html .= $this->parseForum($this->engine->getTemplate('topic_view', 'forums'), $top);
                        }
                        $_template = $this->engine->replaceVariable($match, $_template, $html);
                        break;
                    case 'adminMenu':
                        $html = '';

                        if(!empty($_SESSION['user'])) {
                            $U = new User($this->engine->sql);
                            $user = $U->getUser($_SESSION['user']['id']);

                            if($user->group->admin) {
                                $html = $this->engine->getTemplate('admin_categories', 'admin');
                            }
                        }

                        $_template = $this->engine->replaceVariable($match, $_template, $html);
                        break;
                }
            }

            return $_template;
        }

        public function parseTopic($_template, Topic $_topic) {
            $matches = $this->engine->getPlaceholders($_template);

            $C = new Category($this->engine->sql);
            $cat = $C->getCategory($_topic->categoryId);

            $latest = $_topic->getLatestPost();

            foreach($matches[1] as $match) {
                $template = explode('::', $match);

                switch($template[1]) {
                    case 'header':
                    case 'title':
                        $_template = $this->engine->replaceVariable($match, $_template, $_topic->title);
                        break;
                    case 'description':
                        $_template = $this->engine->replaceVariable($match, $_template, $_topic->description);
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
                            $T = new Topic($this->engine->sql);
                            $tpc = $T->getTopic($latest['thread']->id);

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
                            $avatar = $this->engine->directory . '/_assets/img/' . $template[2];
                        }
                        $_template = $this->engine->replaceVariable($match, $_template, $avatar);
                        break;
                    case 'lastPosterUrl':
                        $url = ($latest['post']->author->username ? '/profile/' . $latest['post']->author->username : '#');
                        $_template = $this->engine->replaceVariable($match, $_template, $url);
                        break;
                    case 'lastPostDate':
                        $date = ($latest['post']->post_date ? MISC::parseDate($latest['post']->post_date, $this->engine->config, array('howLongAgo' => true)) : 'No posts...');
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
                            $U = new User($this->engine->sql);
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
            $matches = $this->engine->getPlaceholders($_template);

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
                        $_template = $this->engine->replaceVariable($match, $_template, $_thread->author->username);
                        break;
                    case 'lastReplyDate':
                        $date = MISC::parseDate($_thread->latestPost->post_date, $this->engine->config, array('howLongAgo' => true));
                        $_template = $this->engine->replaceVariable($match, $_template, $date);
                        break;
                    case 'postDate':
                        $date = MISC::parseDate($_thread->posted, $this->engine->config, array('howLongAgo' => true));
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
                        $T = new Topic($this->engine->sql);
                        $top = $T->getTopic($_thread->topicId);

                        if($top instanceof Topic) {
                            $C = new Category($this->engine->sql);
                            $cat = $C->getCategory($top->categoryId);

                            $_template = $this->engine->replaceVariable($match, $_template,
                                                                        '/forums/' . $cat->getURL() . '/' . $top->getURL() . '/' . $_thread->getURL());
                        }
                        break;
                    case 'latestPostDate':
                        $date = MISC::parseDate($_thread->latestPost->post_date, $this->engine->config, array('howLongAgo' => true));
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
            $matches = $this->engine->getPlaceholders($_template);

            foreach($matches[1] as $match) {
                $template = explode('::', $match);

                switch($template[1]) {
                    case 'id':
                        $_template = $this->engine->replaceVariable($match, $_template, $_post->id);
                        break;
                    case 'poster':
                        $_template = $this->engine->replaceVariable($match, $_template, $_post->author->username);
                        break;
                    case 'posterAvatar':
                        $_template = $this->engine->replaceVariable($match, $_template, $_post->author->avatar);
                        break;
                    case 'posterMemberSince':
                        $date = MISC::parseDate($_post->author->regDate, $this->engine->config, array('howLongAgo' => true));
                        $_template = $this->engine->replaceVariable($match, $_template, $date);
                        break;
                    case 'content':
                        $content = (isset($_post->post_html) ? $_post->post_html : '<p>' . $_post->post_text . '</p>');
                        $_template = $this->engine->replaceVariable($match, $_template, $content);
                        break;
                    case 'posted':
                        $date = MISC::parseDate($_post->post_date, $this->engine->config, array('howLongAgo' => true));
                        $_template = $this->engine->replaceVariable($match, $_template, $date);
                        break;
                    case 'threadTitle':
                        $T = new Thread($this->engine->sql);
                        $trd = $T->getThread($_post->threadId);
                        $_template = $this->engine->replaceVariable($match, $_template, $trd->title);
                        break;
                    case 'posterUrl':
                        $_template = $this->engine->replaceVariable($match, $_template, '/profile/' . $_post->author->username);
                        break;
                    case 'manage':
                        $html = '';

                        if(!empty($_SESSION['user'])) {
                            $U = new User($this->engine->sql);
                            $user = $U->getUser($_SESSION['user']['id']);

                            if($_SESSION['user']['id'] == $_post->author->id
                            || $user->group->admin) {
                                $html = $this->engine->getTemplate('post_view_manage', 'forums');
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