<?php
    namespace SBLib\ThemeEngine;

    use SBLib\Forums\Post;
    use SBLib\Forums\Thread;

    class ForumParser extends MainEngine {

        public function parseThread(Thread $thread, $template) {
            $placeholders = $this->findPlaceholders($template);

            $replacements = [];
            foreach($placeholders as $placeholder) {
                if($placeholder instanceof Placeholder) {
                    switch ($placeholder->getOption()) {
                        case 'title':
                            $replacements[$placeholder->get()] = $thread->title;
                            break;
                        case 'content':
                            $Post = new Post($this->_DBUtil);
                            $firstPost = $Post->getPosts($thread->id)[0];
                            $replacements[$placeholder->get()] = $firstPost->post_html;
                    }
                }
            }

            return str_replace(array_keys($replacements), array_values($replacements), $template);
        }
    }