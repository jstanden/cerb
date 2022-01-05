# horde-imap

This is a fork of [Horde-Imap](https://github.com/horde/Imap_Client) that is compatible with Composer 2.0, which no longer supports loading PEAR repositories.

This fork will be retired when Horde-Imap officially supports Composer 2.0.

To use this dependency, add a custom repository to your `composer.json`:

~~~
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/jstanden/horde-imap"
    }
]
~~~

Then add the project to `require`:

~~~
"require" : {
    "horde/imap-client": "dev-main"
}
~~~