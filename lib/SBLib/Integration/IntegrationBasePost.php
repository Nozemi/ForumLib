<?php
    namespace SBLib\Integration;

    use SBLib\Forums\Post;

    abstract class IntegrationBasePost extends IntegrationBase  {
        abstract public function createPost(Post $post);
        abstract public function getPosts($threadId, Post $post);
        abstract public function getPost($id, Post $post);
        abstract public function updatePost(Post $post);
        abstract public function deletePost($id, Post $post);
    }