<?php
/**
 * @file Comment.php
 *
 * @brief Comments class, later too be used by `CommentMan`
 *
 * @author J. A. Corbal <jacorbal@gmail.com>
 *
 * @date Created: Sat 07 Mar 2020 15:08 UTC
 * @date Updated: Sun 08 Mar 2020 15:18 UTC
 *
 * @version 0.1.1
 *
 * @copyright (c) 2020, J. A. Corbal
 *
 * @note Licensed as BSD 3-clause
 *       <https://opensource.org/licenses/BSD-3-Clause>
 */


/**
 * @class Comment
 *
 * @brief Commentary structure
 *
 * @note It's intended to use one database for all posts post, instead
 *       of on small database for every individual post, hence, it's
 *       needed for a database entry of `post_id`.
 */
class Comment {
    // Attributes
    private $id;            ///< Unique identifier of the comment
    private $parent_id;     ///< Id of the parent comment in replies
    private $post_id;       ///< Id of the post where this comment belongs
    private $username;      ///< Username of the commentator
    private $message;       ///< Content of the message (HTML)
    private $timestamp;     ///< Publication timestamp (UTC, ISO-8601)
    private $ip;            ///< IP address from where this post was sent
    private $is_deleted;    ///< Deleted, but exists to preserve the thread
    private $is_hidden;     ///< Hidden, not to be shown


    // Accessors
    public function __get($property) {
        if (property_exists($this, $property)) {
            return $this->$property;
        }
    }


    // Mutators
    public function __set($property, $value) {
        if (property_exists($this, $property)) {
            $this->$property = trim($value);
        }
        return $this;
    }


    // Operations
    /**
     * @brief Prepare the comment before store in the dabase
     *
     * @detail Intended to sanitize the strings, allow or remove
     *         specific HTML tags, entities, strip tags, optionally
     *         strip or encode special characters, etc.
     */
    public function prepare() {
        $this->username = filter_var($this->username,
            FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $this->message = filter_var($this->message,
            FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        // more operations...
     }


    /**
     * @brief Translate a @e Comment object into an array
     *
     * @param comment The comment object
     *
     * @return An array containing the comment information
     */
    public function toArray() {
        if (!isset($this)) {
            return null;
        }

        $comment_arr = array();
        $comment_arr['id'] = $this->id;
        $comment_arr['parent_id'] = $this->parent_id;
        $comment_arr['post_id'] = $this->post_id;
        $comment_arr['username'] = $this->username;
        $comment_arr['message'] = $this->message;
        $comment_arr['timestamp'] = $this->timestamp;
        $comment_arr['ip'] = $this->ip;
        $comment_arr['is_deleted'] = $this->is_deleted;
        $comment_arr['is_hidden'] = $this->is_hidden;
        $comment_arr['children'] = null;

        return $comment_arr;
    }


    // Display information when invoked as a string
    public function __toString() {
        return "{ " . $this->id .
               ", " . $this->parent_id .
               ", " . $this->post_id .
               ", " . $this->username .
               ", " . $this->message .
               ", " . $this->timestamp .
               ", " . $this->ip .
               ", " . $this->is_deleted .
               ", " . $this->is_hidden .
               " }";
    }
}

