<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SiteController extends AbstractController
{
    /**
     * @Route("/{path}", name="site", requirements={"path"=".*"}, defaults={"path":""})
     * @param string $path
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function index(string $path, Request $request)
    {
        $pubFolder = rtrim('/storage/' . $path, '/');
        $baseDir = $this->getParameter('kernel.project_dir') . '/public' . $pubFolder;

        if ($request->isMethod('POST') && $request->request->getBoolean('delete') && $path !== '') {
            $fs = new Filesystem();

            if ($fs->exists($baseDir)) {
                $fs->remove($baseDir);
            }

            return $this->redirectToRoute('site');
        }

        $linkBase = $path === '' ? '' : trim('/' . $path, '/');

        $depth = $request->query->getInt('depth', 1);

        $finder = new Finder();

        $directories = [];
        $files = [];
        $slides = [];
        $skipped = [];

        if (is_dir($baseDir)) {
            $directoryObjs = $finder->in($baseDir)->sortByName()->directories();
            /** @var SplFileInfo $directoryObj */
            foreach ($directoryObjs as $directoryObj) {
                $link = $linkBase . '/' . $directoryObj->getRelativePathname();
                $directories[$link] = $pubFolder . '/' . $directoryObj->getRelativePathname();
            }

            if ($depth > 0) {
                $finder->depth('< ' . $depth);
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

        ksort($slides);
        sort($files);

        return $this->render('site/index.html.twig', [
            'current' => $pubFolder,
            'path' => $path,
            'files' => $files,
            'directories' => $directories,
            'skipped' => $skipped,
            'slides' => array_values($slides),
            'depth' => $depth,
            'deletable' => $path !== '',
        ]);
    }
}
