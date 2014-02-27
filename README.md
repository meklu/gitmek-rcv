gitmek-rcv
====

Handles GitHub and BitBucket POST hook functionality by passing it to [irker](http://catb.org/~esr/irker/).
Note that the BitBucket support is considerably more fragile.

How the heck do you use this?
====

Set up irker, plop `gitmek-rcv.php` into a publicly accessible directory on your web server,
and copy `gitmek-rcv_config.example.php` to the same path as `gitmek-rcv_config.php`.
Remember to set up the correct repositories and channels though!

Then you can add the URL to your repository as a web hook on either GitHub or BitBucket.

LEGAL
====

The license is as follows:

    /* copyleft 2013-2014 meklu (public domain)
     *
     * any and all re-distributions and/or modifications
     * should or should not include this disclaimer
     * depending on the douchiness-level of the distributor
     * in question
     */

tl;dr: Do whatever the fuck you want with it.

TODO
====

* Pull requests (issue #1)
* Branch/tag creation/deletion (issue #2)
