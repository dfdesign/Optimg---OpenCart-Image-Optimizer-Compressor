<?php
class ControllerExtensionModuleOptimg extends Controller{

    private $error = array();
    const DAT_FILE_PATH = 'cache/optimised.dat';

    public function index() {
        $this->load->language('extension/module/optimg');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('module_optimg', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
        }

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/module/optimg', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['action'] = $this->url->link('extension/module/optimg', 'user_token=' . $this->session->data['user_token'], true);

        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

        if (isset($this->request->post['module_optimg_status'])) {
            $data['module_optimg_status'] = $this->request->post['module_optimg_status'];
        } else {
            $data['module_optimg_status'] = $this->config->get('module_optimg_status');
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        $data['user_token'] = $this->session->data['user_token'];

        $allFilesCount = count(self::getAllImages());
        $optimisedFilesCount = count($this->getOptimisedImages());
        $data['optimisedFilesCount'] = $optimisedFilesCount;
        $data['allFilesCount'] = $allFilesCount;
        $data['percentage'] = round((($optimisedFilesCount / $allFilesCount) * 100));
        $this->response->setOutput($this->load->view('extension/module/optimg', $data));
    }

    // AJAX call method
    public function compress(){
        error_reporting(0);

        $allFiles = self::getAllImages();
        $allFIilesCount = count($allFiles);
        $optimisedFiles = $this->getOptimisedImages();
        $optimisedFilesCount = count(--$optimisedFiles);

        $filesForOptimising = array_values(array_diff($allFiles, $optimisedFiles));
        $percentage = round((($optimisedFilesCount / $allFIilesCount) * 100));
        $response['optimisedFiles'] = $optimisedFilesCount;
        $response['allFiles'] = $allFIilesCount;
        $response['percentage'] = $percentage;

        if($percentage < 100) {
            $file = $this->compressImageRequest($filesForOptimising[0]);
            if($file) {
                if (!copy($file['dest'], realpath(DIR_IMAGE) . $filesForOptimising[0])) {
                    $response['error'] = 'There is issue uploading file.' . $file;
                    $this->response->addHeader('Content-Type: application/json');
                    $this->response->setOutput(json_encode($response));
                } else {
                    file_put_contents(DIR_IMAGE . self::DAT_FILE_PATH, $filesForOptimising[0] . '\r\n', FILE_APPEND);
                    $this->response->addHeader('Content-Type: application/json');
                    $this->response->setOutput(json_encode($response));
                }
            }
        } else {
            $response['success'] = 'Files optimised.';
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($response));
        }
    }

    protected function compressImageRequest($filePath){
        $file = realpath(DIR_IMAGE . $filePath);
        $mime = mime_content_type($file);
        $info = pathinfo($file);
        $name = $info['basename'];
        $output = new CURLFile($file, $mime, $name);
        $data = array(
            "files" => $output,
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://api.resmush.it/?qlty=80');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            $result['error'] = curl_error($ch);
        }
        curl_close($ch);
        return  json_decode($result, true);
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'extension/module/optimg')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

    protected static function getAllImages(){
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(DIR_IMAGE . 'cache'), RecursiveIteratorIterator::CHILD_FIRST);
        $files = [];

        foreach($iterator as $path) {
            if($path->isFile()) {
                $filename = $path->__toString();
                if(strtolower(pathinfo($filename,PATHINFO_EXTENSION)) ==='jpeg' ||
                    strtolower(pathinfo($filename,PATHINFO_EXTENSION)) === 'jpg' ||
                    strtolower(pathinfo($filename,PATHINFO_EXTENSION)) === 'png'){

                    $files[] = str_replace(realpath(DIR_IMAGE), "", realpath($filename));
                }
            }
        }
        return $files;
    }

    protected function getOptimisedImages() {
        $cachedFile = DIR_IMAGE . self::DAT_FILE_PATH;

        if(!file_exists($cachedFile)) {
            return [];
        }

        return explode('\r\n', file_get_contents($cachedFile));
    }

    public function install() {
        $datFile = DIR_IMAGE . 'cache/optimised.dat';
        if(!fopen($datFile, 'w')){
            $this->error['warning'] = $this->language->get('writing_file_permission');
        } else {
            fclose($datFile);
        }
    }

}
