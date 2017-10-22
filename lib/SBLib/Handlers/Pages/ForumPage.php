<?php
    namespace SBLib\Handlers\Pages;

    use SBLib\Handlers\AbstractPage;

    class ForumPage extends AbstractPage {
        protected $_validParams = ['category', 'topic', 'thread'];

        public function __construct($params) {
            parent::__construct($params);

            echo "The forum page.";
        }
    }