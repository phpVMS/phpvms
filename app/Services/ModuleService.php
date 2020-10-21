<?php

namespace App\Services;

use App\Contracts\Service;
use App\Exceptions\ModuleExistsException;
use App\Exceptions\ModuleInstallationError;
use App\Exceptions\ModuleInvalidFileType;
use App\Models\Module;
use Exception;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laracasts\Flash\FlashNotifier;
use Madnest\Madzipper\Madzipper;
use Nwidart\Modules\Json;
use PharData;

class ModuleService extends Service
{
    protected static $adminLinks = [];

    /**
     * @var array 0 == logged out, 1 == logged in
     */
    protected static $frontendLinks = [
        0 => [],
        1 => [],
    ];

    /**
     * Add a module link in the frontend
     *
     * @param string $title
     * @param string $url
     * @param string $icon
     * @param bool   $logged_in
     */
    public function addFrontendLink(string $title, string $url, string $icon = 'pe-7s-users', $logged_in = true)
    {
        self::$frontendLinks[$logged_in][] = [
            'title' => $title,
            'url'   => $url,
            'icon'  => $icon,
        ];
    }

    /**
     * Get all of the frontend links
     *
     * @param mixed $logged_in
     *
     * @return array
     */
    public function getFrontendLinks($logged_in): array
    {
        return self::$frontendLinks[$logged_in];
    }

    /**
     * Add a module link in the admin panel
     *
     * @param string $title
     * @param string $url
     * @param string $icon
     */
    public function addAdminLink(string $title, string $url, string $icon = 'pe-7s-users')
    {
        self::$adminLinks[] = [
            'title' => $title,
            'url'   => $url,
            'icon'  => $icon,
        ];
    }

    /**
     * Get all of the module links in the admin panel
     *
     * @return array
     */
    public function getAdminLinks(): array
    {
        return self::$adminLinks;
    }

    /**
     * Get All modules from Database
     *
     * @return object
     */
    public function getAllModules(): object
    {
        return Module::all();
    }

    /**
     * Get Module Information from Database.
     *
     * @param $id
     *
     * @return object
     */
    public function getModule($id): object
    {
        return Module::find($id);
    }

    /**
     * Adding installed module to the database
     *
     * @param $module_name
     *
     * @return bool
     */
    public function addModule($module_name): bool
    {
        /*Check if module already exists*/
        $module = Module::where('name', $module_name);
        if (!$module->exists()) {
            Module::create([
                'name'    => $module_name,
                'enabled' => 1,
            ]);
            Artisan::call('module:migrate '.$module);
            return true;
        }
        return false;
    }

    /**
     * User's uploaded file is passed into this method
     * to install module in the Storage.
     *
     * @param UploadedFile $file
     *
     * @return FlashNotifier
     */
    public function installModule(UploadedFile $file): FlashNotifier
    {
        $file_ext = $file->getClientOriginalExtension();
        $allowed_extensions = ['zip', 'tar', 'gz'];

        if (!in_array($file_ext, $allowed_extensions, true)) {
            throw new ModuleInvalidFileType();
        }

        $module = null;

        $new_dir = rand();

        File::makeDirectory(
            storage_path('app/tmp/modules/'.$new_dir),
            0777,
            true
        );
        $temp_ext_folder = storage_path('app/tmp/modules/'.$new_dir);
        $temp = storage_path('app/tmp/modules/'.$new_dir);

        $zipper = null;

        if ($file_ext === 'tar' || $file_ext === 'gz') {
            $zipper = new PharData($file);
            $zipper->decompress();
        }

        if ($file_ext === 'zip') {
            $madZipper = new Madzipper();

            try {
                $zipper = $madZipper->make($file);
            } catch (Exception $e) {
                throw new ModuleInstallationError();
            }
        }

        try {
            $zipper->extractTo($temp);
        } catch (Exception $e) {
            throw new ModuleInstallationError();
        }

        if (!File::exists($temp.'/module.json')) {
            $directories = Storage::directories('tmp/modules/'.$new_dir);
            $temp = storage_path('app/'.$directories[0]);
        }

        $json_file = $temp.'/module.json';

        if (File::exists($json_file)) {
            $json = json_decode(file_get_contents($json_file), true);
            $module = $json['name'];
        } else {
            File::deleteDirectory($temp_ext_folder);
            return flash()->error('Module Structure Not Correct!');
        }

        if (!$module) {
            File::deleteDirectory($temp_ext_folder);
            return flash()->error('Not a Valid Module File.');
        }

        $toCopy = base_path().'/modules/'.$module;

        if (File::exists($toCopy)) {
            File::deleteDirectory($temp_ext_folder);

            throw new ModuleExistsException($module);
        }

        File::moveDirectory($temp, $toCopy);

        File::deleteDirectory($temp_ext_folder);

        try {
            $this->addModule($module);
        } catch (Exception $e) {
            throw new ModuleExistsException($module);
        }

        Artisan::call('config:cache');

        return flash()->success('Module Installed');
    }

    /**
     * Update module with the status passed by user.
     *
     * @param $id
     * @param $status
     *
     * @return bool
     */
    public function updateModule($id, $status): bool
    {
        $module = Module::find($id);
        $module->update([
            'enabled' => $status,
        ]);
        if ($status === 1) {
            Artisan::call('module:migrate '.$module);
        }
        return true;
    }

    /**
     * Delete Module from the Storage & Database.
     *
     * @param $id
     * @param $data
     *
     * @return bool
     */
    public function deleteModule($id, $data): bool
    {
        $module = Module::find($id);
        if ($data['verify'] === strtoupper($module->name)) {
            try {
                $module->delete();
            } catch (Exception $e) {
                Log::emergency('Cannot Delete Module!');
            }
            $moduleDir = base_path().'/modules/'.$module->name;

            try {
                File::deleteDirectory($moduleDir);
            } catch (Exception $e) {
                Log::info('Folder Deleted Manually for Module : '.$module->name);
                return true;
            }
            return true;
        }
        return false;
    }

    /**
     * Get & scan all modules.
     *
     * @return array
     */
    public function scan()
    {
        $modules_path = base_path('modules/*');
        $path = Str::endsWith($modules_path, '/*') ? $modules_path : Str::finish($modules_path, '/*');

        $modules = [];

        $manifests = (new Filesystem())->glob("{$path}/module.json");

        is_array($manifests) || $manifests = [];

        foreach ($manifests as $manifest) {
            $name = Json::make($manifest)->get('name');
            $module = Module::where('name', $name);
            if (!$module->exists()) {
                array_push($modules, $name);
            }
        }

        return $modules;
    }
}
