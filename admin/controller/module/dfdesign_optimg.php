<?php
class ControllerModuleDfdesignOptimg extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('module/dfdesign_optimg');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('extension/module');

        $data['heading_title'] = $this->language->get('heading_title'); 
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_module'),
            'href' => $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL')
        );
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        if (!isset($this->request->get['module_id'])) {
            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('heading_title'),
                'href' => $this->url->link('module/dfdesign_optimg', 'token=' . $this->session->data['token'], 'SSL')
            );
        }else{
            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('heading_title'),
                'href' => $this->url->link('module/dfdesign_optimg', 'token=' . $this->session->data['token'] . '&module_id=' . $this->request->get['module_id'], 'SSL')
            );
        }

        define('WEBSERVICE', 'http://api.resmush.it/ws.php?img=');
        define('QUALITY', '90');
        define('DAT_FILE_PATH', 'cache/optimised.dat');

        $optimisedFiles = self::getAlreadyOptimisedFiles();
        $allImages = self::getAllImages();
        $allFiles = $allImages['files'];
        $data['optimisedFiles'] = $optimisedFiles;
        $data['notOptimisedFiles'] = count($allFiles) - count($optimisedFiles);

        if(count($allFiles) - count($optimisedFiles) <= 0){
            $data['notOptimisedFiles'] == 0;
        }

        if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            if(!empty($_POST['action'])) {

                foreach ($allFiles as $key => $value) {
                    if (in_array($allFiles[$key]['name'], $optimisedFiles)) {
                        unset($allFiles[$key]);
                    }
                }

                $allFilesNew = array_values($allFiles);

                if(count($allFiles) > 0) {
                    $imgUrl = explode('public_html', $allFilesNew[0]['name'], 2);
                    $oldSize = $allFilesNew[0]['size'];
                    $imageFullUrlWithSpaces = 'http://' . $_SERVER['SERVER_NAME'] . $imgUrl[1];
                    $imageFullUrl = str_replace(' ','%20', $imageFullUrlWithSpaces);
                    $prefixUrl = explode("public_html",DIR_IMAGE,2);
                    $relPathNewUrl = $prefixUrl[0] . "public_html" . $imgUrl[1];

                    $o = json_decode(file_get_contents(WEBSERVICE . $imageFullUrl . '&qlty=' . QUALITY));

                    if(isset($o->error)){
                        echo json_encode('ERROR ' . $o->error . $imageFullUrl );
                    }

                    if(copy($o->dest, $relPathNewUrl)){
                        $newImg = get_headers($o->dest, 1);
                        file_put_contents(DIR_IMAGE . DAT_FILE_PATH, $relPathNewUrl . '\r\n', FILE_APPEND);

                        $response['url'] = $relPathNewUrl;
                        $response['oldSize'] = '<td>'. $oldSize . '</td>';
                        $response['newSize'] = '<td><span>' . $newImg["Content-Length"] . '<span></td>';
                        $response['optiSize'] = 100 - (number_format(($newImg["Content-Length"] / $oldSize ) * 100,2));
                        $response['optimisedNum'] = $optimisedFiles++;
                        $response['notOptimisedNum'] = $data['notOptimisedFiles']--;
                    }else{
                        echo json_encode('Error. Please, check help page.');
                    }
                }
                echo json_encode($response);
            }
        }

        $this->response->setOutput($this->load->view('module/dfdesign_optimg.tpl', $data));
    }

    public static function getAllImages(){
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(DIR_IMAGE . 'cache'), RecursiveIteratorIterator::CHILD_FIRST);
        $files = array();
        $size = 0;

        foreach($iterator as $path) {
            if($path->isFile()) {
                $filename = $path->__toString();
                if(strtolower(pathinfo($filename,PATHINFO_EXTENSION)) ==='jpeg' ||
                strtolower(pathinfo($filename,PATHINFO_EXTENSION)) === 'jpg' ||
                strtolower(pathinfo($filename,PATHINFO_EXTENSION)) === 'png'){
                    
                    $filesize = filesize($filename);
                    $size += $filesize;
                    $files[] = array(
                        'name' => $filename,
                        'size' =>  $filesize
                    );
                }
            }
        }
        return array(
            'total' => $size,
            'files' => $files
        );
    }

    public static function getOptimisedImages() {
        $cachedFile = DIR_IMAGE . DAT_FILE_PATH;

        if(!file_exists($cachedFile)) {
            $files = '';
            return $files;
        }

        $files = explode('\r\n', file_get_contents($cachedFile));

        return $files;
    }

    public static function getAlreadyOptimisedFiles(){
        $optimisedImages = self::getOptimisedImages();
        $optimised = count($optimisedImages);

        if($optimisedImages == ''){
            $optimised =0;
        }

        return $optimised;
    }

}