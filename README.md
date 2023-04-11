# WebFileManager

This is a simple file manager to use with server-side scripts only.

It is useful when there is an Unrestricted File Upload vulnerability and the application
has restrictions on forking processes.

It is also stealthier than executing system commands on a server. But it is restricted to
the allowed commands of the web application scripting language.

**Note**: the commands are *Unix-like*.

## PHP WebFileManager

Commands included so far:
- `base64`; `decode`
- `cat`
- `cp`; `to`
- `curl`; `data`, `method`, `output` (untested)
- `date`
- `du`
- `file`
- `id`
- `ln`
- `ls`
- `md5sum`
- `mkdir`
- `mv`
- `pwd`
- `realpath`
- `rm`
- `rmdir`
- `sed`; `l0`, `l1`
- `sha1sum`
- `wc`
- `whoami`
- `xxd`; `decode`, `to`

Extra
- `basic`: avoids the usage of POSIX functions.
- `error`: shows the errors (if shown by the application).
- `include`: includes a file.
- `info`: runs `phpinfo()`
- `web`: shows the output within `<pre>` HTML tags.

# TODO

- [ ] Implement more functions:
    - [ ] chmod
    - [ ] chown
    - [ ] chgrp
    - [ ] dig
    - [ ] dirname
    - [ ] df
    - [ ] find
    - [ ] grep
    - [ ] head
    - [ ] ip
    - [ ] rev
    - [ ] tac
    - [ ] tail
    - [ ] umask
- [ ] Improve functions/options:
    - [ ] human-readable file sizes with `ls`.
    - [ ] create parent directories with `mkdir`.
- [ ] Use a commandline-like syntax.
- [ ] Use different languages:
    - [ ] Java
    - [ ] ASP.NET

