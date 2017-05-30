<?php
    namespace ForumLib\Integration;

    use ForumLib\Forums\Various;

    abstract class IntegrationBaseVarious extends IntegrationBase {
        abstract public function getLatestPosts(Various $various);
    }