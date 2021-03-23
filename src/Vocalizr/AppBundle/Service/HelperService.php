<?php

namespace Vocalizr\AppBundle\Service;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Bridge\Twig\TwigEngine;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Process\Process;

class HelperService
{
    /**
     * HelperService constructor.
     * @param RegistryInterface $doctrine
     * @param ContainerInterface $container
     * @param TwigEngine $templating
     */
    public function __construct($doctrine, $container, $templating)
    {
        $this->em         = $doctrine->getEntityManager();
        $this->container  = $container;
        $this->templating = $templating;
    }

    /**
     * Milliseconds to time (m:s)
     *
     * @param int $milliseconds
     *
     * @return string
     */
    public function millisecondsToTime($milliseconds)
    {
        $minutes = floor(($milliseconds % (1000 * 60 * 60)) / (1000 * 60));
        $seconds = floor((($milliseconds % (1000 * 60 * 60)) % (1000 * 60)) / 1000);

        return $minutes . ':' . str_pad($seconds, 2, 0, STR_PAD_LEFT);
    }

    /**
     * Get temp directory for uploaded files
     *
     * @return string
     */
    public function getUploadTmpDir()
    {
        return $this->container->get('kernel')->getRootdir() . '/../tmp';
    }

    public function getWaveformDir()
    {
        return $this->container->get('kernel')->getRootdir() . '/../web/waveform';
    }

    /**
     * Run command
     */
    public function exec($cmd)
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
            pclose(popen('start /B ' . $cmd, 'r'));
        } else {
            $process = new Process($cmd . ' > /dev/null &');
            $process->setTimeout(10);
            $process->run();
            //echo $cmd.' > /dev/null 2>&1', "r";
            //pclose(popen($cmd.' > /home/solved/public_html/genwaveform.result &', "r"));
            //shell_exec($cmd.' > /dev/null 2>&1');
            //$cmd = $cmd . '> /home/solved/public_html/genwaveform.result &';
            //print $cmd;
        }
    }

    public function execSfCmd($cmd)
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
            pclose(popen('start /B php ' . $this->container->get('kernel')->getRootdir() . '/console ' . $cmd, 'r'));
        } else {
            $phpPath = exec('which php');
            $cmd     = $phpPath . ' ' . $this->container->get('kernel')->getRootdir() . '/console ' . $cmd . ' > /dev/null &';
            exec($cmd);
        }
    }

    /**
     * Run lame executable
     * This helper was created so it can run on both WINDOWS and LINUX
     *
     * @param string $cmd
     */
    public function execLame($cmd)
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
            pclose(popen('start /B lame ' . $cmd, 'r'));
        } else {
            $lamePath = exec('which lame');
            exec($lamePath . ' ' . $cmd);
        }
    }

    /**
     * @param string $sourceFile
     * @param string $destinationFile
     */
    public function convertToMp3($sourceFile, $destinationFile)
    {
        // Use joint stereo as recommended encoding mode.
        $mode = 'j';
        $bitrate = 96;
        $mediaInfo = $this->container->get('vocalizr_app.media_info');
        $analyze = $mediaInfo->analyzeAudio($sourceFile);

        if (isset($analyze['channels']) && $analyze['channels'] == 1) {
            // If source audio is in mono, use mono mode and drop bitrate to 64kbps.
            $mode = 'm';
            $bitrate = 64;
        }

        $this->execLame(sprintf(
            '-h -m %s -b %d %s %s',
            $mode,
            $bitrate,
            escapeshellarg($sourceFile),
            escapeshellarg($destinationFile)
        ));
    }

    public function addPricePercent($price, $percent, $format = false)
    {
        $total_price = $price *= (1 + $percent / 100);
        if ($format) {
            return sprintf('%.2f', $total_price);
        }
        return $total_price;
    }

    public function getPricePercent($price, $percent, $format = true)
    {
        $price = ($price / 100) * $percent;
        if (!$format) {
            return $price;
        }
        return sprintf('%.2f', $price);
    }

    /**
     * Get money string as integer
     * Strip out all characters
     *
     * @param string $value
     */
    public function getMoneyAsInt($value)
    {
        // strip out commas
        $value = str_replace(',', '', $value);
        // strip out all but numbers, dash, and dot
        //$value = preg_replace("/([^0-9\.])/i","",$value);
        // make sure we are dealing with a proper number now
        if (!is_numeric($value)) {
            return 0;
        }
        // convert to a float explicitly
        return $value;
    }

    public function streamAudio($file, Request $request = null)
    {
        $content_type = 'audio/mpeg';
        // Get file size
        $filesize = sprintf('%u', filesize($file));

        // Handle 'Range' header
        if (isset($_SERVER['HTTP_RANGE'])) {
            $range = $_SERVER['HTTP_RANGE'];
        } elseif (function_exists('apache_request_headers') && ($apache = apache_request_headers())) {
            $headers = [];
            foreach ($apache as $header => $val) {
                $headers[strtolower($header)] = $val;
            }
            if (isset($headers['range'])) {
                $range = $headers['range'];
            } else {
                $range = false;
            }
        } elseif ($request && $request->headers->has('range')) {
            $range = $request->headers->get('range');
        } else {
            $range = false;
        }

        //Is range
        if ($range) {
            $partial             = true;
            list($param, $range) = explode('=', $range);
            // Bad request - range unit is not 'bytes'
            if (strtolower(trim($param)) != 'bytes') {
                header('HTTP/1.1 400 Invalid Request');
                exit;
            }
            // Get range values
            $range = explode(',', $range);
            $range = explode('-', $range[0]);
            // Deal with range values
            if ($range[0] === '') {
                $end   = $filesize - 1;
                $start = $end - intval($range[0]);
            } elseif ($range[1] === '') {
                $start = intval($range[0]);
                $end   = $filesize - 1;
            } else {
                // Both numbers present, return specific range
                $start = intval($range[0]);
                $end   = intval($range[1]);
                if ($end >= $filesize || (!$start && (!$end || $end == ($filesize - 1)))) {
                    $partial = false;
                } // Invalid range/whole file specified, return whole file
            }
            $length = $end - $start + 1;
        }
        // No range requested
        else {
            $partial = false;
        }

        // Send standard headers
        header("Content-Type: $content_type");
        header("Content-Length: $filesize");
        header('Accept-Ranges: bytes');

        // send extra headers for range handling...
        if ($partial) {
            header('HTTP/1.1 206 Partial Content');
            header("Content-Range: bytes $start-$end/$filesize");
            if (!$fp = fopen($file, 'rb')) {
                header('HTTP/1.1 500 Internal Server Error');
                exit;
            }
            if ($start) {
                fseek($fp, $start);
            }
            while ($length) {
                set_time_limit(0);
                $read = ($length > 8192) ? 8192 : $length;
                $length -= $read;
                echo fread($fp, $read);
            }
            fclose($fp);
        }
        //just send the whole file
        else {
            readfile($file);
        }
        exit;
    }

    public function slugify($text)
    {
        // replace non letter or digits by -
        $text = preg_replace('~[^\\pL\d]+~u', '-', $text);

        // trim
        $text = trim($text, '-');

        // transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

        // lowercase
        $text = strtolower($text);

        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        if (empty($text)) {
            return 'n-a';
        }

        return $text;
    }
}