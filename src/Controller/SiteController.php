<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Routing\Annotation\Route;

class SiteController extends AbstractController
{
    /**
     * @Route("/{path}", name="site", requirements={"path"=".*"})
     * @param string $path
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function index(string $path = '')
    {
//        $pubFolder = '/storage/' . ($path === '' ? '' : ('' . $path));
        $pubFolder = ($path === '' ? '' : ('/' . $path));
//        $pubFolder = '/storage' . ($path === '' ? '' : ('/' . $path));
//        $pubFolder = ($path === '' ? '' : ('/' . $path));
//        $baseLink = '/' . $path;
        $finder = new Finder();
        $baseDir = $this->getParameter('kernel.project_dir') . '/public/storage' . $pubFolder;

//        var_dump($baseDir);
//        die("\n" . __METHOD__ . ":" . __FILE__ . ":" . __LINE__ . "\n");

        $directories = [];
        $files = [];
        $slides = [];

        $skipped = [];

        if (is_dir($baseDir)) {
            $directoryObjs = $finder->in($baseDir)->sortByName()->directories();
            /** @var SplFileInfo $directoryObj */
            foreach ($directoryObjs as $directoryObj) {
                $link = $this->generateUrl('site', ['path' => $directoryObj->getRelativePathname()]);
                $directories[$link] = $pubFolder . '/' . $directoryObj->getRelativePathname();
            }

            $fileObjs = $finder->in($baseDir)->sortByName()->files()->depth(0);
//            $files = $finder->in($baseDir)->sortByName()->files()->name(['*.jpeg', '*.jpg', '*.gif', '*.png', '*.svg']);

            /** @var SplFileInfo $fileObj */
            foreach ($fileObjs as $fileObj) {
                dump($fileObj);

                $src = $pubFolder . '/' . $fileObj->getRelativePathname();
                $ext = mb_strtolower($fileObj->getExtension());

                switch ($ext) {
                    case 'jpeg':
                    case 'gif':
                    case 'bmp':
                    case 'png':
                    case 'jpg':
                        $size = getimagesize($fileObj->getPathname());

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

                $files[] = $src; // we need it only for debug now
            }
        }

        return $this->render('site/index.html.twig', [
            'path' => $pubFolder,
            'files' => $files,
            'directories' => $directories,
            'skipped' => $skipped,
            'slides' => $slides,
        ]);
    }
}
