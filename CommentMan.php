<?php
/**
 * @file comments.php
 *
 * @brief Comments manager without using any public identification
 *        service, and storing & retrieving the data using SQLite3
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

include_once('Comment.php');


/**
 * @class CommentMan
 *
 * @brief Comments manager using database in SQLite3
 *
 * @see Comment
 */
 /* Interface:
  *
  * is_empty()
  *     Test if the database is empty or not
  *
  * length()
  *     Return the number of registers in the database
  *
  * create()
  *     Create a new empty structure for the DB, an empty table
  *
  * add(comment)
  *     Add a new record to the database
  *
  * rem_by_id(id)
  *     Remove a comment by identifier `id`
  *
  * rem_by_name(name)
  *     Remove all registers with username `name`
  *
  * rem_newer_than(s)
  *     Remove all registers newer than `s`, e.g.: `rem_older("1 day")`
  *
  * rem_older_than(s)
  *     Remove all registers older than `s`, e.g.: `rem_older("1 year")`
  *
  * fetch_by_id(id)
  *     Retrieve a unique comment corresponding to the unique `id`
  *
  * fetch_by_post(pid)
  *     Retrieve an array of comments for that particular post `pid`
  *
  * fetch_thread(pid)
  *     Retrieve a multidimensional array of the comments thread for
  *     the post `pid` sorted hierarchically, as a tree
  */
class CommentMan extends SQLite3 {
    /**
     * @brief Default constructor.
     *        Start a new database connection
     *
     * @param database    Path to the database
     * @param create_file Create the file (if possible) if it doesn't exist
     *
     * @note If @e database doesn't exist, create it if @e create is set
     *       to @c true, or exit
     */
    public function __construct($database, $create_file=false) {
        if (!is_file($database)) {
            trigger_error("CommentMan::ctor: no such database: $database");
            if ($create_file) {
                if (fopen($database, "w") and !is_file($database)) {
                    trigger_error("CommentMan::ctor: couldn't create file: $database");
                    die("Cannot create database file (check permissions)");
                }
            } else {// if no file, and not wanting to create it... die
                die("Cannot work without a database");
            }
        }

        // Start DB connection
        $this->open($database);

        // If database is empty, create a new schema
        if ($this->is_empty()) {
            if (!$this->create()) {
                trigger_error("CommentMan:ctor: couldn't create database table");
                die();
            }
        }
    }


    /**
     * @brief Default destructor.
     *        Close database connection
     */
    public function __destruct() {
        $this->close();
    }


    /**
     * @brief Count the number of registers stored in the database
     *
     * @return Number of registers in the database
     */
    public function length() {
        $sql = <<<EOQ
            SELECT COUNT(*)
                FROM comments;
EOQ;

        return $this->querySingle($sql);
    }


    /**
     * @brief Test if the database lacks an structure
     *
     * @return @c true if empty, or otherwise
     */
     public function is_empty() {
         $sql = <<<EOQ
            SELECT COUNT(name) FROM sqlite_master
                WHERE type = 'table'
                    AND NAME = 'comments';
EOQ;

        return $this->querySingle($sql) === 0;
     }


    /**
     * @brief Create a new structure for the database
     *
     * @return @c true if successfully created, or otherwise
     */
    public function create() {
        $sql = <<<EOQ
            CREATE TABLE comments
                (id         INTEGER PRIMARY KEY AUTOINCREMENT,
                 parent_id  INTEGER             NOT_NULL        DEFAULT 0,
                 post_id    INTEGER             NOT_NULL,
                 username   NVARCHAR(80),
                 message    TEXT                NOT_NULL,
                 timestamp  DATETIME            DEFAULT         CURRENT_TIMESTAMP,
                 ip         VARCHAR(50),
                 is_deleted BOOLEAN             NOT_NULL        DEFAULT 0,
                 is_hidden  BOOLEAN             NOT_NULL        DEFAULT 0);
EOQ;

        return $this->query($sql);
    }


    /**
     * @brief Add a new comment to the database
     *
     * @param comment_obj Comment object to add
     *
     * @return @c true if the insertion was successful, or otherwise
     *
     * @note This is the only piece of code that takes input query
     *       values from outside the class, so there's the STMT
     *       (statement) method to prevent SQL injection, although
     *       another test will be made from the PHP side when getting
     *       the data, just in case.
     */
    public function add($comment_obj) {
        $sql = <<<EOQ
            INSERT INTO comments
                (parent_id, post_id, username, message, ip)
            VALUES
                (:parent_id, :post_id, :username, :message, :ip);
EOQ;

        $stmt = $this->prepare($sql);
        $stmt->bindValue(':parent_id', $comment_obj->parent_id, SQLITE3_INTEGER);
        $stmt->bindValue(':post_id',   $comment_obj->post_id,   SQLITE3_INTEGER);
        $stmt->bindValue(':username',  $comment_obj->username,  SQLITE3_TEXT);
        $stmt->bindValue(':message',   $comment_obj->message,   SQLITE3_TEXT);
        $stmt->bindValue(':ip',        $comment_obj->ip,        SQLITE3_TEXT);

        return $stmt->execute();
    }


    /**
     * @brief Remove comment from the database by identifier
     *
     * @param comment_id Identifier of the comment to remove
     *
     * @return @c true if remove was successful, or otherwise
     */
    public function rem_by_id($comment_id) {
        $sql = <<<EOQ
            DELETE FROM comments
                WHERE id="$comment_id";
EOQ;

        return $this->query($sql);
    }


    /**
     * @brief Remove all comments posted by the same user
     *
     * @param username Username to delete all comments from
     *
     * @return @c true if remove was successful, or otherwise
     */
    public function rem_by_name($username) {
        $sql = <<<EOQ
            DELETE FROM comments
                WHERE username="$username";
EOQ;

        return $this->query($sql);
    }


    /**
     * @brief Remove all comments posted newer than a time mark
     *
     * @param newer_than Newness of the registers to delete
     *
     * @return @c true if remove was successful, or otherwise
     *
     * @note The newness must be introduced in a format that SQL knows,
     *       such as "1 day", "6 months", "3 years", "20 minutes",...
     */
    public function rem_newer_than($newer_than="1 day") {
        $sql = <<<EOQ
            DELETE FROM comments
                WHERE timestamp >= datetime('now','-$newer_than');
EOQ;

        return $this->query($sql);
    }


    /**
     * @brief Remove all comments posted older than a time mark
     *
     * @param older_than Oldness of the registers to delete
     *
     * @return @c true if remove was successful, or otherwise
     *
     * @note The oldness must be introduced in a format that SQL knows,
     *       such as "1 day", "6 months", "3 years", "20 minutes",...
     */
    public function rem_older_than($older_than="6 months") {
        $sql = <<<EOQ
            DELETE FROM comments
                WHERE timestamp <= datetime('now','-$older_than');
EOQ;

        return $this->query($sql);
    }


    /**
     * @brief Retrieve a comment from the database by identifier
     *
     * @param id Identifier of the comment to retrieve
     *
     * @return Comment object
     *
     * @see Comment
     */
    public function fetch_by_id($id) {
        $sql = <<<EOQ
            SELECT * FROM comments
                WHERE id = $id;
EOQ;

        $comment = new Comment();
        $row = $this->query($sql)->fetchArray(SQLITE3_ASSOC);
        if (isset($row['id'])) {
            $comment->id = $row['id'];
            $comment->parent_id = $row['parent_id'];
            $comment->post_id= $row['post_id'];
            $comment->username = $row['username'];
            $comment->message = $row['message'];
            $comment->timestamp = $row['timestamp'];
            $comment->ip = $row['ip'];
            $comment->is_deleted = $row['is_deleted'];
            $comment->is_hidden = $row['is_hidden'];
        } else {
            return null;
        }

        return $comment;
    }


    /**
     * @brief Retrieve all comments relative to a single post
     *
     * @param post_id Identifier of the post those comments belong
     * @param limit   Maximum number of comments to retrieve
     *
     * @return Array of comment objects for that post
     *
     * @note The query will be performed sorted by date and reply,
             earliest first
     */
    public function fetch_by_post($post_id, $limit=100) {
        $sql = <<<EOQ
            SELECT * FROM comments
                WHERE post_id = $post_id
                    ORDER BY timestamp ASC, parent_id ASC
                        LIMIT $limit;
EOQ;

        $comments = array();
        $query_res = $this->query($sql);
        while ($row = $query_res->fetchArray(SQLITE3_ASSOC)) {
            $comment = new Comment();
            $comment = $this->fetch_by_id($row['id']);
            array_push($comments, $comment);
        }

        return $comments;
    }


    /**
     * @brief Retrieve the post thread sorted hierarchically as an array
     *
     * @param post_id Identifier of the post to retrieve the thread from
     * @param limit   Maximum number of comments to retrieve
     *
     * @return Multidimensional array (tree) of comments and replies
     *
     * @see build_thread
     */
    public function fetch_thread($post_id, $limit=100) {
        $comments = $this->fetch_by_post($post_id);
        $thread = $this->build_thread($comments);

        return $thread;
    }


    /**
     * @brief Generate the thread array (tree-like) where the comments
     *        are sorted hierarchically, i.e., tree branches are the
     *        children comments associated to a parent comment (replies)
     *
     * @param comments  Array of comment objects
     * @param parent_id Identifier of the comment to get the thread of
     *
     * @return Tree-like array ordered by hierarchy parent/children
     */
    private function build_thread(array $comments, $parent_id=0) {
        $thread = array();

        foreach ($comments as $comment) {
            $comment_arr = $comment->toArray();
            if ($comment_arr['parent_id'] == $parent_id) {
                $children = $this->build_thread($comments,
                                                $comment_arr['id']);
                if ($children) {
                    $comment_arr['children'] = $children;
                }
                $thread[$comment_arr['id']] = $comment_arr;
                unset($comment_arr);
            }
        }

        return $thread;
    }

}

