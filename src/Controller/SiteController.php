<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Routing\Annotation\Route;

class SiteController extends AbstractController
{
    /**
     * @Route("/", name="site")
     */
    public function index()
    {
        $pubFolder = '/storage';
        $finder = new Finder();
        $baseDir = $this->getParameter('kernel.project_dir') . '/public' . $pubFolder;

        $directories = [];
        $files = [];
        $slides = [];

        $skipped = [];

        if (is_dir($baseDir)) {
            $directoryObjs = $finder->in($baseDir)->sortByName()->directories();
            /** @var SplFileInfo $directoryObj */
            foreach ($directoryObjs as $directoryObj) {
                $directories[] = $pubFolder . '/' . $directoryObj->getRelativePathname();
            }

            $fileObjs = $finder->in($baseDir)->sortByName()->files();
//            $files = $finder->in($baseDir)->sortByName()->files()->name(['*.jpeg', '*.jpg', '*.gif', '*.png', '*.svg']);

            /** @var SplFileInfo $fileObj */
            foreach ($fileObjs as $fileObj) {
                $src = $pubFolder . '/' . $fileObj->getRelativePathname();
                $ext = mb_strtolower($fileObj->getExtension());

                switch ($ext) {
                    case 'jpeg':
                    case 'gif':
                    case 'bmp':
                    case 'png':
                    case 'jpg':
                        $size = getimagesize($fileObj->getPathname());

//                        dump($fileObj->getPath());
//                        dump($size);
//                        die("\n" . __METHOD__ . ":" . __FILE__ . ":" . __LINE__ . "\n");

                        $slides[] = [
                            'src' => $src,
                            'w' => $size[0],
                            'h' => $size[1],
                            'title' => $fileObj->getFilename(),
                        ];
                        break;
                    default:
                        $skipped[] = $src;
                }

//                var_dump($fileObj->getFileInfo());
//                var_dump($fileObj->getExtension());
//                die("\n" . __METHOD__ . ":" . __FILE__ . ":" . __LINE__ . "\n");
//                $img = imagecreatefromstr

                $files[] = $src;
            }

//            die("\n" . __METHOD__ . ":" . __FILE__ . ":" . __LINE__ . "\n");
        }

//        dump($skipped);
//        dump($slides);
//
//        die("\n" . __METHOD__ . ":" . __FILE__ . ":" . __LINE__ . "\n");

        return $this->render('site/index.html.twig', [
            'path' => $pubFolder,
            'files' => $files,
            'directories' => $directories,
            'skipped' => $skipped,
            'slides' => $slides,
        ]);
    }
}
