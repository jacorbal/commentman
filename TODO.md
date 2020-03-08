TO-DO list
==========

  * Better way to prepare comments:
    * Allowing some tags
    * Generating valid HTML

  * Limited thread:
    * Add interval to fetch the thread in order to allow paginating

        cm->build_thread($comments, $parent_id, $from, $to);

  * Test potential interlocking when executing simultaneous query from
   different users

  * Register, user database to prevent identity theft
    * Login from external accounts such as Google, Twitter, etc.  OpenID?

