<?php
    namespace SBLib\Integration;

    use SBLib\Forums\Various;

    abstract class IntegrationBaseVarious extends IntegrationBase {
        abstract public function getLatestPosts(Various $various);
    }