<?php

namespace Vocalizr\AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

define('ZOOM', 2);                 // Image is drawn ZOOM times bigger and then resized
define('ACCURACY', 400);           // Data point is the average of ACCURACY points in the data block
define('WIDTH', 850);             // image width
define('HEIGHT', 70);             // image heigt
define('FOREGROUND', '#FFFFFF');
define('BACKGROUND', '');  //  blank for transparent

class GenerateWaveformCommand extends ContainerAwareCommand
{
    const AUDIO_TYPE_USER = 'user_audio';

    const AUDIO_TYPE_PROJECT = 'project_audio';

    const AUDIO_TYPE_BID = 'bid_audio';

    const AUDIO_TYPE_ASSET = 'asset_audio';

    public $FOREGROUND = '#FFFFFF';

    private $audioType;

    protected function configure()
    {
        $this
                ->setName('vocalizr:generate-waveform')
                ->setDescription('Generate waveform for mp3 files')
                ->addArgument('id', InputArgument::REQUIRED, 'Primary ID for audio')
                ->addOption('user_audio', 'ua', null, 'User Audio')
                ->addOption('project_audio', 'pa', null, 'Project Audio')
                ->addOption('project_bid', 'pb', null, 'Project Bid')
                ->addOption('project_asset', 'pas', null, 'Project Asset')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container         = $this->getContainer();
        $doctrine          = $container->get('doctrine');
        $em                = $doctrine->getManager();
        $userAudioRepo     = $doctrine->getRepository('VocalizrAppBundle:UserAudio');
        $projectAudioRepo  = $doctrine->getRepository('VocalizrAppBundle:ProjectAudio');
        $projectBidRepo    = $doctrine->getRepository('VocalizrAppBundle:ProjectBid');
        $projectAssetsRepo = $doctrine->getRepository('VocalizrAppBundle:ProjectAsset');

        $id = $input->getArgument('id');

        $tmpDir      = $container->get('service.helper')->getUploadTmpDir();
        $waveformDir = $container->get('service.helper')->getWaveformDir();

        $audio = false;
        if ($input->getOption('user_audio')) {
            error_log('USER AUDIO');
            $audio           = $userAudioRepo->find($id);
            $this->audioType = self::AUDIO_TYPE_USER;
        } elseif ($input->getOption('project_audio')) {
            error_log('PROJECT AUDIO');
            $audio           = $projectAudioRepo->find($id);
            $this->audioType = self::AUDIO_TYPE_PROJECT;
        } elseif ($input->getOption('project_bid')) {
            error_log('PROJECT BID');
            $audio           = $projectBidRepo->find($id);
            $this->audioType = self::AUDIO_TYPE_BID;
        } elseif ($input->getOption('project_asset')) {
            error_log('PROJECT ASSET');
            $audio           = $projectAssetsRepo->find($id);
            $this->audioType = self::AUDIO_TYPE_ASSET;
        }

        // Get audio file
        if (!$audio) {
            error_log('GENERATED WAVE FORM: Invalid audio: ' . $id);
            exit;
        }

        // temporary file name
        $tmpname = $tmpDir . '/' . substr(md5(time()), 0, 10);

        // Use preview audio for assets.
        if ($this->audioType === self::AUDIO_TYPE_ASSET) {
            $audioPath = $audio->getAbsolutePreviewPath();
        } else {
            $audioPath = $audio->getAbsolutePath();
        }

        // copy from mp3 file to tmp file
        if (!copy($audioPath, "{$tmpname}_o.mp3")) {
            error_log('Failed to copy file from ' . $audioPath . ' -> ' . "{$tmpname}_o.mp3");
            exit;
        }

        // support for stereo waveform?
        $stereo = false;

        // array of wavs that need to be processed
        $wavs_to_process = [];

        /**
         * convert mp3 to wav using lame decoder
         * First, resample the original mp3 using as mono (-m m), 16 bit (-b 16), and 8 KHz (--resample 8)
         * Secondly, convert that resampled mp3 into a wav
         * We don't necessarily need high quality audio to produce a waveform, doing this process reduces the WAV
         * to it's simplest form and makes processing significantly faster
         */
        if ($stereo) {
            // scale right channel down (a scale of 0 does not work)
            exec("/usr/bin/lame {$tmpname}_o.mp3 --scale-r 0.1 -m m -S -f -b 16 --resample 8 {$tmpname}.mp3 && /usr/bin/lame -S --decode {$tmpname}.mp3 {$tmpname}_l.wav");
            //exec("lame {$tmpname}_o.mp3 --scale-r 0.1 -m m -S -f -b 16 --resample 8 {$tmpname}.mp3 && lame -S --decode {$tmpname}.mp3 {$tmpname}_l.wav");
            // same as above, left channel
            exec("/usr/bin/lame {$tmpname}_o.mp3 --scale-l 0.1 -m m -S -f -b 16 --resample 8 {$tmpname}.mp3 && /usr/bin/lame -S --decode {$tmpname}.mp3 {$tmpname}_r.wav");
            //exec("lame {$tmpname}_o.mp3 --scale-l 0.1 -m m -S -f -b 16 --resample 8 {$tmpname}.mp3 && lame -S --decode {$tmpname}.mp3 {$tmpname}_r.wav");
            $wavs_to_process[] = "{$tmpname}_l.wav";
            $wavs_to_process[] = "{$tmpname}_r.wav";
        } else {
            exec("/usr/bin/lame {$tmpname}_o.mp3 -m m -S -f -b 16 --resample 8 {$tmpname}.mp3 && /usr/bin/lame -S --decode {$tmpname}.mp3 {$tmpname}.wav");
            //exec("lame {$tmpname}_o.mp3 -m m -S -f -b 16 --resample 8 {$tmpname}.mp3 && lame -S --decode {$tmpname}.mp3 {$tmpname}.wav");
            $wavs_to_process[] = "{$tmpname}.wav";
        }

        // delete temporary files
        unlink("{$tmpname}_o.mp3");
        unlink("{$tmpname}.mp3");

        // Genereate a 96 bitrate version of mp3 file
        // @TODO: Check what current version of bitrate is, if it's 128 or less don't bothers
        exec('/usr/bin/lame ' . $audio->getAbsolutePath() . ' --abr 96 ' . $audio->getAbsolutePath());

        if (!file_exists($waveformDir)) {
            mkdir($waveformDir, 0777, true);
        }

        if ($input->getOption('project_bid')) {
            $waveFormImageName     = $waveformDir . '/' . $audio->getUuid() . '.png';
            $waveFormImageRollName = $waveformDir . '/' . $audio->getUuid() . '-roll.png';
        } else {
            $waveFormImageName     = $waveformDir . '/' . $audio->getSlug() . '.png';
            $waveFormImageRollName = $waveformDir . '/' . $audio->getSlug() . '-roll.png';
        }

        $this->FOREGROUND = '#FFFFFF';

        $this->drawWaveform("{$tmpname}.wav", $waveFormImageName);

        $this->FOREGROUND = '#14b8d6';

        $this->drawWaveform("{$tmpname}.wav", $waveFormImageRollName);
        unlink("{$tmpname}.wav");

        $audio->setWaveGenerated(true);
        $em->persist($audio);
        $em->flush();

        $output->writeln('Done');
    }

    /**
     * GENERAL FUNCTIONS
     */
    private function drawWaveform($wavfilename, $pngfilename)
    {
        // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
        // Create image
        // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
        $img = imagecreatetruecolor(WIDTH * ZOOM, HEIGHT * ZOOM);

        // fill background of image
        if (BACKGROUND == '') {
            imagesavealpha($img, true);
            $transparentColor = imagecolorallocatealpha($img, 0, 0, 0, 127);
            imagefill($img, 0, 0, $transparentColor);
        } else {
            list($r, $g, $b) = $this->html2rgb(BACKGROUND);
            imagefilledrectangle($img, 0, 0, WIDTH * ZOOM, HEIGHT * ZOOM, imagecolorallocate($img, $r, $g, $b));
        }

        // generate foreground color
        list($r, $g, $b) = $this->html2rgb($this->FOREGROUND);

        // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
        // Read wave header
        // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
        $handle = fopen($wavfilename, 'rb');

        $heading[] = fread($handle, 4);
        $heading[] = bin2hex(fread($handle, 4));
        $heading[] = fread($handle, 4);
        $heading[] = fread($handle, 4);
        $heading[] = bin2hex(fread($handle, 4));
        $heading[] = bin2hex(fread($handle, 2));
        $heading[] = bin2hex(fread($handle, 2));
        $heading[] = bin2hex(fread($handle, 4));
        $heading[] = bin2hex(fread($handle, 4));
        $heading[] = bin2hex(fread($handle, 2));
        $heading[] = bin2hex(fread($handle, 2));
        $heading[] = fread($handle, 4);
        $heading[] = bin2hex(fread($handle, 4));

        if ($heading[5] != '0100') {
            die('ERROR: wave file should be a PCM file');
        }

        $peek    = hexdec(substr($heading[10], 0, 2));
        $byte    = $peek / 8;
        $channel = hexdec(substr($heading[6], 0, 2));

        // point = one data point (pixel), WIDTH * ZOOM total
        // block = one block, there are $accuracy blocks per point
        // chunk = one data point 8 or 16 bit, mono or stereo
        $filesize  = filesize($wavfilename);
        $chunksize = $byte * $channel;

        $file_chunks = ($filesize - 44) / $chunksize;
        //if ($file_chunks < WIDTH*ZOOM) die("ERROR: wave file has $file_chunks chunks, ".(WIDTH*ZOOM)." required.");
        if ($file_chunks < WIDTH * ZOOM * ACCURACY) {
            $accuracy = 1;
        } else {
            $accuracy = ACCURACY;
        }
        $point_chunks = $file_chunks / (WIDTH * ZOOM);
        $block_chunks = $file_chunks / (WIDTH * ZOOM * $accuracy);

        $blocks                = [];
        $points                = 0;
        $current_file_position = 44.0; // float, because chunks/point and clunks/block are floats too.
        fseek($handle, 44);

        // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
        // Read the data points and draw the image
        // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
        while (!feof($handle)) {
            // The next file position is the float value rounded to the closest chunk
            // Read the next block, take the first value (of the first channel)
            $real_pos_diff = ($current_file_position - 44) % $chunksize;
            if ($real_pos_diff > ($chunksize / 2)) {
                $real_pos_diff -= $chunksize;
            }
            fseek($handle, $current_file_position - $real_pos_diff);

            $chunk = fread($handle, $chunksize);
            if (feof($handle) && !strlen($chunk)) {
                break;
            }

            $current_file_position += $block_chunks * $chunksize;

            if ($byte == 1) {
                $blocks[] = ord($chunk[0]);
            } // 8 bit
            else {
                $blocks[] = ord($chunk[1]) ^ 128;
            } // 16 bit

            // Do we have enough blocks for the current point?
            if (count($blocks) >= $accuracy) {
                // Calculate the mean and add the peak value to the array of blocks
                sort($blocks);
                $mean = (count($blocks) % 2) ? $blocks[(count($blocks) - 1) / 2]
                                             : ($blocks[count($blocks) / 2] + $blocks[count($blocks) / 2 - 1]) / 2
                                             ;
                if ($mean > 127) {
                    $point = array_pop($blocks);
                } else {
                    $point = array_shift($blocks);
                }

                // Draw
                $lineheight = round($point / 255 * HEIGHT * ZOOM);
                imageline($img, $points, 0 + (HEIGHT * ZOOM - $lineheight), $points, HEIGHT * ZOOM - (HEIGHT * ZOOM - $lineheight), imagecolorallocate($img, $r, $g, $b));
                // update vars
                $points++;
                $blocks = [];
            }
        }

        // close wave file
        fclose($handle);

        if (ZOOM > 0) {
            // resample the image to the proportions defined in the form
            $rimg = imagecreatetruecolor(WIDTH, HEIGHT);
            // save alpha from original image
            imagesavealpha($rimg, true);
            imagealphablending($rimg, false);
            // copy to resized
            imagecopyresampled($rimg, $img, 0, 0, 0, 0, WIDTH, HEIGHT, WIDTH * ZOOM, HEIGHT); // Half wave form
            //imagecopyresampled($rimg, $img, 0, 0, 0, 0, WIDTH, HEIGHT, WIDTH*ZOOM, HEIGHT);
            imageline($rimg, 0, HEIGHT - 1, WIDTH, HEIGHT - 1, imagecolorallocate($img, $r, $g, $b));
            imagepng($rimg, $pngfilename);
            imagedestroy($rimg);
        } else {
            imageline($img, 0, round(HEIGHT / 2), WIDTH * ZOOM, round(HEIGHT / 2), imagecolorallocate($img, $r, $g, $b));
            imagepng($img, $pngfilename);
        }

        imagedestroy($img);
    }

    private function getPeaks($wavFilename)
    {
    }

    private function findValues($byte1, $byte2)
    {
        $byte1 = hexdec(bin2hex($byte1));
        $byte2 = hexdec(bin2hex($byte2));
        return $byte1 + ($byte2 * 256);
    }

    /**
     * Great function slightly modified as posted by Minux at
     * http://forums.clantemplates.com/showthread.php?t=133805
     */
    private function html2rgb($input)
    {
        $input = ($input[0] == '#') ? substr($input, 1, 6) : substr($input, 0, 6);
        return [
            hexdec(substr($input, 0, 2)),
            hexdec(substr($input, 2, 2)),
            hexdec(substr($input, 4, 2)),
        ];
    }
}