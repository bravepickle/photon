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

        $response = $this->redirectToRoute('site', ['path' => $request->query->get('url')]);
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

        [$directories, $files, $slides, $skipped] = $this->initFilesData($baseDir, $linkBase, $depth, $pubFolder);

        $breadcrumbs = $this->buildBreadcrumbs($pubFolder);

        [$theme, $themeIconsMap, $themeLabelsMap] = $this->initThemeData($request);

        [$prevLink, $nextLink] = $this->initPrevNextLinks($baseDir, $linkBase);

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
            'prevLink' => $prevLink,
            'nextLink' => $nextLink,
        ]);
    }

    protected function initPrevNextLinks(string $currentPath, string $urlPath): array
    {
        $pos = mb_strrpos($urlPath, '/');
        if (!$urlPath || $pos === false) {
            return [null, null]; // cannot get siblings for root and first level folders
        }

        $prevLink = null;
        $nextLink = null;

        $currentFolder = mb_substr($urlPath, $pos + 1);
        $basePath = mb_substr($urlPath, 0, $pos);

        $finder = new Finder();

        $fileObjs = $finder->in(dirname($currentPath))->sortByName()->directories()->depth('== 0');

        $found = false;
        foreach ($fileObjs as $fileObj) {
            if ($found) {
                $nextLink = $basePath . '/' . $fileObj->getRelativePathname();

                break;
            }

            if ($fileObj->getRelativePathname() === $currentFolder) {
                $found = true;

                continue;
            }

            $prevLink = $basePath . '/' . $fileObj->getRelativePathname();
        }

        if (!$found) {
            return [null, null];
        }

        return [trim($prevLink, '/'), trim($nextLink, '/')];
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

    /**
     * @param Request $request
     * @return array
     */
    public function initThemeData(Request $request): array
    {
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
        return [$theme, $themeIconsMap, $themeLabelsMap];
    }

    /**
     * @param Finder $finder
     * @param string $baseDir
     * @param string $pubFolder
     * @param array $slides
     * @param array $files
     * @param array $skipped
     * @return array
     */
    public function initSlidesData(Finder $finder, string $baseDir, string $pubFolder, array $slides, array $files, array $skipped): array
    {
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

                    $files[$src] = $fileObj->getRelativePathname();
                    break;
                default:
                    $skipped[$src] = $fileObj->getRelativePathname();
            }
        }

        return [$slides, $files, $skipped];
    }

    /**
     * @param string $baseDir
     * @param string $linkBase
     * @param int $depth
     * @param string $pubFolder
     * @return array
     */
    public function initFilesData(string $baseDir, string $linkBase, int $depth, string $pubFolder): array
    {
        $finder = new Finder();

        $directories = [];
        $files = [];
        $slides = [];
        $skipped = [];

        if (is_dir($baseDir)) {
            $directoryObjs = $finder->in($baseDir)->sortByName()->directories()
                ->depth(($depth > 0 ? '< ' . $depth : '>= 1'));

            /** @var SplFileInfo $directoryObj */
            foreach ($directoryObjs as $directoryObj) {
                $link = $linkBase . '/' . $directoryObj->getRelativePathname();
                $directories[$link] = $directoryObj->getRelativePathname();
            }

            [$slides, $files, $skipped] =
                $this->initSlidesData($finder, $baseDir, $pubFolder, $slides, $files, $skipped);
        }

        ksort($slides);
        asort($files);
        asort($skipped);

        return [$directories, $files, $slides, $skipped];
    }

}
