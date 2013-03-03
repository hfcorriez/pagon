<?php
use Pagon\Helper\Cli;
use Pagon\Helper\Debug;

function console_output($text, $color, $bg_color, $length = 80)
{
    if (is_array($text)) {
        foreach ($text as $i => $t) {
            $text[$i] = console_output($t, $color, $bg_color);
        }
        return join(PHP_EOL, $text);
    }
    return Cli::text(str_pad(substr($text, 0, $length), $length, ' '), $color, $bg_color);
}

?>
<?php echo
console_output(
    array(
        '  ' . str_repeat('-', 78),
        str_pad("  $type [$code] " . str_replace(getcwd(), '', $file) . " $line", 80, ' ', STR_PAD_RIGHT),
        '  ' . str_repeat('-', 78)
    )
    , 'white', 'red');

$source = PHP_EOL . Debug::source($file, $line);
$source = str_replace(array('<span class="line">', '</span>', '</code></pre>', '<pre class="source"><code>', '<span class="number">'), '', $source);
$source = htmlspecialchars_decode($source);
$source = preg_replace('/<span class="line highlight">(.*)/', Cli::text('$1', 'white', 'red'), $source);
$source = str_replace("\n", "\n" . Cli::text('  ', 'white', 'red'), $source);
echo $source . Cli::text(str_repeat(' ', 78), 'white', 'red') . PHP_EOL;
echo console_output(array(
    '  Tracked messages:',
    '  ' . str_repeat('-', 78)
), 'white', 'red') . PHP_EOL;
echo Cli::text('  ', 'white', 'red') . str_replace("\n", "\n" . Cli::text('  ', 'white', 'red'), str_replace(getcwd(), '', $message)) . PHP_EOL;
echo console_output('', 'white', 'red', 80) . PHP_EOL;
?>
