<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SiteController extends AbstractController
{
    /**
     * @Route("/{path}", name="site", requirements={"path"=".*"})
     * @param string $path
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function index(string $path = '', Request $request)
    {
//        if ($path !== '') {
        $linkBase = $path === '' ? '' : trim('/' . $path, '/');
//        }

        $depth = $request->query->getInt('depth', 1);

//        $pubFolder = '/storage/' . ($path === '' ? '' : ('' . $path));
//        $pubFolder = ($path === '' ? '' : ('/' . $path));
//        $pubFolder = '/storage' . ($path === '' ? '' : ('/' . $path));
//        $pubFolder = '/storage' . ($path === '' ? '' : $path);
        $pubFolder = rtrim('/storage/' . $path, '/');
//        $linkBase = $path;
//
//        dump($path);
//        dump($linkBase);
//        dump($pubFolder);
//
//        die("\n" . __METHOD__ . ":" . __FILE__ . ":" . __LINE__ . "\n");
////        $pubFolder = ($path === '' ? '' : ('/' . $path));
//        $baseLink = '/' . $path;
        $finder = new Finder();
        $baseDir = $this->getParameter('kernel.project_dir') . '/public' . $pubFolder;
//        $baseDir = $this->getParameter('kernel.project_dir') . '/public/storage' . $pubFolder;

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
//                $link = $this->generateUrl('site', ['path' => $directoryObj->getRelativePathname()]);
                $link = $linkBase . '/' . $directoryObj->getRelativePathname();
                $directories[$link] = $pubFolder . '/' . $directoryObj->getRelativePathname();
            }

            if ($depth > 0) {
                $finder->depth('< ' . $depth);
            }

//            var_dump($depth);
//            die("\n" . __METHOD__ . ":" . __FILE__ . ":" . __LINE__ . "\n");

            $fileObjs = $finder->in($baseDir)->sortByName()->files();
//            $fileObjs = $finder->in($baseDir)->sortByName()->files()->depth(0);
//            $files = $finder->in($baseDir)->sortByName()->files()->name(['*.jpeg', '*.jpg', '*.gif', '*.png', '*.svg']);

            /** @var SplFileInfo $fileObj */
            foreach ($fileObjs as $fileObj) {
                $src = $pubFolder . '/' . $fileObj->getRelativePathname();
                $ext = mb_strtolower($fileObj->getExtension());

                switch ($ext) {
                    case 'jpeg':
                    case 'gif':
                    case 'bmp':
                    case 'ico':
                    case 'png':
                    case 'jpg':
                        $size = getimagesize($fileObj->getPathname());

                        $slides[$src] = [
                            'src' => $src,
                            'w' => $size[0],
                            'h' => $size[1],
                            'title' => $fileObj->getFilename(),
                        ];
                        break;
                    default:
                        $skipped[] = $src;
                }

                $files[$src] = $src; // we need it only for debug now
            }
        }

//        die("\n" . __METHOD__ . ":" . __FILE__ . ":" . __LINE__ . "\n");

        ksort($slides);
        sort($files);

//        echo '<pre>';
//        print_r($files);
//        die("\n" . __METHOD__ . ":" . __FILE__ . ":" . __LINE__ . "\n");

        return $this->render('site/index.html.twig', [
            'current' => $pubFolder,
            'path' => $path,
            'files' => $files,
            'directories' => $directories,
            'skipped' => $skipped,
            'slides' => array_values($slides),
            'depth' => $depth,
        ]);
    }
}
