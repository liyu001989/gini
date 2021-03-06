<?php

namespace Gini\Dev;

// require_once __DIR__ . "/macro.php";
require_once __DIR__ . "/obfuscator.php";

class Packer
{
    private $file;
    private $phar;

    public function __construct($file)
    {
        $this->phar = [];
        $this->file = $file;
        /*
        if (ini_get('phar.readonly')) {
            die ("Please set \x1b[1mphar.readonly=Off\x1b[0m in php.ini\n");
        }
        ini_set('phar.readonly', false);

        $file = "$file.phar";
        // touch($file);
        try {
            $this->phar = new \Phar($file, 0, basename($file));
        } catch (UnexpectedValueException $e) {
            @unlink($file);
            $this->phar = new \Phar($file, 0, basename($file));
        }

        $this->phar->setStub('<?php echo "GINI PACK FILE.\n"; __HALT_COMPILER();?>');
        */
    }

    private static function relativePath($path, $base)
    {
        return preg_replace('|^'.preg_quote($base, '|').'/?(.*)$|', '$1', $path);
    }

    public function import($path)
    {
        $ite = new \RecursiveDirectoryIterator($path);
        foreach (new \RecursiveIteratorIterator($ite) as $filename => $fileinfo) {
            $name = basename($filename);
            if ($name[0] == '.') continue;
            $this->encode_file($filename, $path);
        }

    }

    public function finish()
    {
        foreach ($this->phar as $f => $cnt) {
            $file = $this->file . '/' . $f;
            $dir = dirname($file);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            file_put_contents($file, $cnt);
        }
    }   

    public function encode_file($path, $base)
    {
        $rpath = self::relativePath($path, $base);

        if (preg_match('/\.(php|phtml)$/', $path)) {
            echo "    \e[34mPHP\e[0m: $rpath...";
            //移除脚本中的注释和回车
            $content = file_get_contents($path);
            $total = strlen($content);

            // 预编译代码
            if (class_exists('\\Gini\\Dev\\Macro')) {
                $content = Macro::compile($content);
            }

            // 混淆变量
            if (class_exists('\\Gini\\Dev\\Obfuscator')) {
                $ob = new Obfuscator($content);
                $ob->set_reserved_keywords(['$config', '$lang']);
                $content = $ob->format();
            }

            $converted = strlen($content);
            $percentage = round($converted * 100 / $total, 1);
            echo " $percentage%";
        } elseif (fnmatch("*.js", $path)) {
            echo "     \e[32mJS\e[0m: $rpath...";

            $content = @file_get_contents($path);
            $total = strlen($content);

            $content = shell_exec('uglifyjs '.escapeshellarg($path) . ' 2>&1');
            $converted = strlen($content);

            $percentage = round($converted * 100 / $total, 1);
            echo " $percentage%";
        } elseif (fnmatch('*.css', $path)) {
            echo "    \e[35mCSS\e[0m: $rpath...";
            $content = @file_get_contents($path);
            $total = strlen($content);

            $content = \CSSMin::minify($content);
            $converted = strlen($content);

            $percentage = round($converted * 100 / $total, 1);
            echo " $percentage%";
        } else {
            echo "    DUP: $rpath...";
            //复制相关文件
            $content = @file_get_contents($path);
            $total = strlen($content);
            echo "$total bytes";
        }

        if ($content) $this->phar[$rpath] = $content;
        else echo "... EMPTY";
        echo "\n";
    }

}
