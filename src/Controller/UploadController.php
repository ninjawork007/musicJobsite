<?php

namespace App\Controller;

use App\Service\HelperService;
use getID3;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class UploadController extends AbstractController
{
    /**
     * @Route("/upload/audio", name="upload_audio")
     * @Route("/upload", name="upload")
     *
     * @param HelperService $helper
     *
     * @return JsonResponse
     */
    public function indexAction(HelperService $helper)
    {
        // Make sure file is not cached (as it happens for example on iOS devices)
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');

        // 5 minutes execution time
        @set_time_limit(0);

        // Uncomment this one to fake upload time
        // usleep(5000);
        // Settings
        $targetDir = $helper->getUploadTmpDir();
        //$targetDir = 'uploads';
        $cleanupTargetDir = false; // Remove old files
        $maxFileAge       = 15 * 3600; // Temp file age in seconds
        //
        // Create target dir
        if (!file_exists($targetDir)) {
            @mkdir($targetDir);
        }

        $errorDetails = '';

        // 0-------------------------
        if (empty($_FILES) || $_FILES['file']['error']) {
            $data = [
                'OK'   => 0,
                'info' => "",
            ];

            if (isset($_FILES['file']['error']) && !empty($_FILES['file']['error'])) {
                $errorDetails = $_FILES['file']['error'];
            }

            return $this->getErrorResponse('Failed to move uploaded file.', $errorDetails);
        }

        $chunk  = isset($_REQUEST['chunk']) ? intval($_REQUEST['chunk']) : 0;
        $chunks = isset($_REQUEST['chunks']) ? intval($_REQUEST['chunks']) : 0;

        $fileName = isset($_REQUEST['name']) ? $_REQUEST['name'] : $_FILES['file']['name'];
        $filePath = $targetDir . '/' . $fileName;

        // Open temp file
        $out = @fopen("{$filePath}.part", $chunk == 0 ? 'wb' : 'ab');
        if ($out) {
            // Read binary input stream and append it to temp file
            $in = @fopen($_FILES['file']['tmp_name'], 'rb');

            if ($in) {
                while ($buff = fread($in, 4096)) {
                    fwrite($out, $buff);
                }
            } else {
                return $this->getErrorResponse('Failed to open input stream.');
            }

            @fclose($in);
            @fclose($out);

            @unlink($_FILES['file']['tmp_name']);
        } else {
            return $this->getErrorResponse('Failed to open output stream.');
        }

        // Check if file has been uploaded
        if (!$chunks || $chunk == $chunks - 1) {
            // Strip the temp .part suffix off
            rename(sprintf('%s.part', $filePath), $filePath);

            // If file uploaded from "Studio Message Board" and has AIFF extension
            // Then convert it to MP3
            $fileInfo = (new getid3())->analyze($filePath);
            if (isset($_REQUEST['isUploadedFromSMB']) && $_REQUEST['isUploadedFromSMB'] === '1' && isset($fileInfo['fileformat']) && $fileInfo['fileformat'] === 'aiff') {
                $info        = pathinfo($filePath);
                $newFilePath = sprintf('%s/%s.mp3', $info['dirname'], $info['filename']);

                $helper->execLame(sprintf('-h -m m -b 64 %s %s', $filePath, $newFilePath));
            }
        }

        // Remove old temp files
        if ($cleanupTargetDir) {
            if (!is_dir($targetDir) || !$dir = opendir($targetDir)) {
                die('{"jsonrpc" : "2.0", "error" : {"code": 100, "message": "Failed to open temp directory."}, "id" : "id"}');
            }

            while (($file = readdir($dir)) !== false) {
                $tmpfilePath = $targetDir . DIRECTORY_SEPARATOR . $file;

                // If temp file is current file proceed to the next
                if ($tmpfilePath == "{$filePath}.part") {
                    continue;
                }

                // Remove temp file if it is older than the max age and is not the current file
                if (preg_match('/\.part$/', $file) && (filemtime($tmpfilePath) < time() - $maxFileAge)) {
                    @unlink($tmpfilePath);
                }
            }
            closedir($dir);
        }

        die('{"OK": 1, "info": "Upload successful."}');
    }

    /**
     * @Route("/upload/record", name="upload_record")
     *
     * @param Request       $request
     * @param HelperService $helper
     *
     */
    public function recordAction(Request $request, HelperService $helper)
    {
//        $helper      = $this->container->get('service.helper');
        $upload_path = $helper->getUploadTmpDir();

        if (!isset($_FILES['audio']['tmp_name'])) {
            die('1');
            throw $this->createNotFoundException('Failed to upload 1');
        }
        // Check for error
        if ($_FILES['audio']['error']) {
            die('2');
            throw $this->createNotFoundException('Failed to upload 2');
        }

        // Check for filename
        if (!isset($_POST['name'])) {
            die('3');
            throw $this->createNotFoundException('Failed to upload 3');
        }

        $tmpFile  = $_FILES['audio']['tmp_name'];
        $filename = $_POST['name'];

        if (file_exists($upload_path . '/' . $filename)) {
            unlink($upload_path . '/' . $filename);
        }

        // Execute lame mp3 command to convert wav file to mp3
        $cmd = '--abr 128 ' . $tmpFile . ' ' . realpath($upload_path) . '/' . $filename;
        $helper->execLame($cmd);

        $i = 0;
        while (!file_exists(realpath($upload_path) . '/' . $filename)) {
            $i++;
            sleep(1);
            if ($i == 15) {
                throw $this->createNotFoundException('Failed to upload 4');
            }
        }
        die('success');
    }

    /**
     * Play audio from tmp upload directory
     *
     * @Route("/upload/audio/tmp", name="upload_audio_tmp")
     * @param HelperService $helper
     *
     */
    public function audioTmpAction(Request $request, HelperService $helper)
    {
//        $dir     = $this->container->get('service.helper')->getUploadTmpDir();
//        $request = $this->getRequest();
        $dir     = $helper->getUploadTmpDir();

        if (!$file = $request->get('f')) {
            throw $this->createNotFoundException();
        }

        $audioFile = $dir . DIRECTORY_SEPARATOR . $file;

        // Does the file exist?
        if (!file_exists($audioFile)) {
            throw $this->createNotFoundException();
        }

        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Type: audio/mpeg');
        header('X-Pad: avoid browser bug');
        header('Content-Disposition: filename="' . basename($audioFile) . '"');
        header('Cache-Control: no-cache');
        header('Content-Length: ' . filesize($audioFile));
        flush();
        readfile($audioFile);
        die;
    }

    /**
     * @param string $info
     * @param string $details
     * @param array $data
     * @return JsonResponse
     */
    private function getErrorResponse($info, $details = '', $data = [])
    {
        $data['OK'] = false;
        $data['info'] = $info;
        $data['details'] = $details;

        return new JsonResponse($data, 200);
    }
}
