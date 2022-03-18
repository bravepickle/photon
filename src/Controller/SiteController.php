<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

class SiteController extends AbstractController
{
    const COOKIE_THEME_NAME = 'theme';

    const DARK_THEME = 'dark-theme';
    const MONOCHROME_DARK_THEME = 'monochrome-dark-theme';
    const LIGHT_THEME = 'light-theme';

    /**
     * @Route("/switch-theme/{name}", name="switch-theme", requirements={"name"="^[^/]+"}, defaults={"name": "dark-theme"})
     * @param string $name
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function switchTheme(string $name, Request $request)
    {
        // todo: go to src url
        $allowedThemes = [
            self::DARK_THEME,
            self::MONOCHROME_DARK_THEME,
            self::LIGHT_THEME,
        ];

        if (!in_array($name, $allowedThemes)) {
            throw new BadRequestHttpException('Unknown theme was selected: ' . $name);
        }

        $response = $this->redirectToRoute('site', ['path' => $request->query->getAlpha('url')]);
        $response->headers->setCookie(Cookie::create(self::COOKIE_THEME_NAME, $name, '+3 months'));

        return $response;
    }

    /**
     * @Route("/{path}", name="site", requirements={"path"=".*"}, defaults={"path":""})
     * @param string $path
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function index(string $path, Request $request)
    {
        $pubFolder = rtrim($this->getParameter('app_storage_public_path'), '/');

        if ($path) {
            $pubFolder .= '/' . rtrim($path, '/');
        }

        $baseDir = $this->getParameter('kernel.project_dir') . '/public' . $pubFolder;

        if (!is_dir($baseDir)) { // base dir for files not found
            return $this->redirectToRoute('site');
        }

        if ($request->isMethod('POST') && $request->request->getBoolean('delete') && $path !== '') {
            return $this->deleteFolder($path, $baseDir);
        }

        $linkBase = $path === '' ? '' : ('/' . trim($path, '/'));

        $depth = $request->query->getInt('depth', 1);

        $finder = new Finder();

        $directories = [];
        $files = [];
        $slides = [];
        $skipped = [];

        if (is_dir($baseDir)) {
            $directoryObjs = $finder->in($baseDir)->sortByName()->directories()->depth('< 2');
            /** @var SplFileInfo $directoryObj */
            foreach ($directoryObjs as $directoryObj) {
                $link = $linkBase . '/' . $directoryObj->getRelativePathname();
//                $directories[$link] = $pubFolder . '/' . $directoryObj->getRelativePathname();
                $directories[$link] = $directoryObj->getRelativePathname();
            }

            if ($depth > 0) {
                $finder->depth('< ' . $depth);
            }

            $fileObjs = $finder->in($baseDir)->sortByName()->files();

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

        $breadcrumbs = $this->buildBreadcrumbs($pubFolder);

        $theme = $request->cookies->get(self::COOKIE_THEME_NAME, self::DARK_THEME);

        $themeIconsMap = [
            self::DARK_THEME => 'fas fa-moon',
            self::LIGHT_THEME => 'fas fa-sun',
            self::MONOCHROME_DARK_THEME => 'fas fa-adjust',
        ];

        $themeLabelsMap = [
            self::DARK_THEME => 'Dark',
            self::LIGHT_THEME => 'Light',
            self::MONOCHROME_DARK_THEME => 'Monochrome',
        ];

        if (!isset($themeLabelsMap[$theme])) { // unknown theme
            $theme = self::DARK_THEME;
            $request->cookies->remove(self::COOKIE_THEME_NAME);
        }

        return $this->render('site/index.html.twig', [
            'path' => $path,
            'breadcrumbs' => $breadcrumbs,
            'files' => $files,
            'directories' => $directories,
            'skipped' => $skipped,
            'slides' => array_values($slides),
            'depth' => $depth,
            'theme' => $theme,
            'themeLabel' => $themeLabelsMap[$theme],
            'themeIcon' => $themeIconsMap[$theme],
            'deletable' => $path !== '',
        ]);
    }

    /**
     * @param string $pubFolder
     * @return array
     */
    protected function buildBreadcrumbs(string $pubFolder): array
    {
        $breadcrumbs = [];
        $pathCrumbs = [];
        $parts = explode('/', ltrim($pubFolder, '/'));
        $first = 0;
        $last = count($parts) - 1;

        foreach ($parts as $key => $part) {
            if ($key === $first) {
                $breadcrumb = [
                    'url' => $this->generateUrl('site'),
                    'title' => $part,
                ];
            } elseif ($key === $last) {
                $breadcrumb = [
                    'url' => null,
                    'title' => $part,
                ];
            } else {
                $pathCrumbs[] = $part;

                $breadcrumb = [
                    'url' => $this->generateUrl('site', ['path' => implode('/', $pathCrumbs)]),
                    'title' => $part,
                ];
            }

            $breadcrumbs[] = $breadcrumb;
        }

        return $breadcrumbs;
    }

    /**
     * @param string $path
     * @param string $baseDir
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    protected function deleteFolder(string $path, string $baseDir): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        $fs = new Filesystem();

        $redirectPath = $path;

        if ($fs->exists($baseDir)) {
            $fs->remove($baseDir);

            $pathSlugs = explode('/', $redirectPath);
            if (count($pathSlugs) > 1) {
                unset($pathSlugs[count($pathSlugs) - 1]);
                $redirectPath = implode('/', $pathSlugs);
            } else {
                $redirectPath = '';
            }
        }

        return $this->redirectToRoute('site', ['path' => $redirectPath]);
    }

}
