<?php
namespace Laralib\L5scaffold\Makes;

use Illuminate\Console\AppNamespaceDetectorTrait;
use Illuminate\Filesystem\Filesystem;
use Laralib\L5scaffold\Commands\ScaffoldMakeCommand;
use Laralib\L5scaffold\Validators\SchemaParser as ValidatorParser;
use Laralib\L5scaffold\Validators\SyntaxBuilder as ValidatorSyntax;


class MakeService
{
    use AppNamespaceDetectorTrait, MakerTrait;

    /**
     * Store name from Model
     *
     * @var ScaffoldMakeCommand
     */
    protected $scaffoldCommandObj;

    /**
     * Create a new instance.
     *
     * @param ScaffoldMakeCommand $scaffoldCommand
     * @param Filesystem $files
     * @return void
     */
    function __construct(ScaffoldMakeCommand $scaffoldCommand, Filesystem $files)
    {
        $this->files = $files;
        $this->scaffoldCommandObj = $scaffoldCommand;

        $this->createService();
        $this->createServiceInterface();
    }

    /**
     * Start make service.
     *
     * @return void
     */
    private function createService()
    {
        $name = $this->scaffoldCommandObj->getObjName('Name') . 'Service';
        $path = $this->getPath($name, 'service');


        if ($this->files->exists($path)) 
        {
            return $this->scaffoldCommandObj->comment("x $name");
        }

        $this->makeDirectory($path);

        $this->files->put($path, $this->compileServiceStub());

        $this->scaffoldCommandObj->info('+ Service');
    }

    /**
     * Start make service interface.
     *
     * @return void
     */
    private function createServiceInterface()
    {
        $name = $this->scaffoldCommandObj->getObjName('Name') . 'ServiceInterface';
        $path = $this->getPath($name, 'service');


        if ($this->files->exists($path))
        {
            return $this->scaffoldCommandObj->comment("x $name");
        }

        $this->makeDirectory($path);

        $this->files->put($path, $this->compileServiceInterfaceStub());

        $this->scaffoldCommandObj->info('+ Service Interface');
    }

    /**
     * Compile the service stub.
     *
     * @return string
     */
    protected function compileServiceStub()
    {
        $stub = $this->files->get(substr(__DIR__,0, -5) . 'Stubs/service.stub');
        $this->buildStub($this->scaffoldCommandObj->getMeta(), $stub);

        return $stub;
    }

    /**
     * Compile the service stub.
     *
     * @return string
     */
    protected function compileServiceInterfaceStub()
    {
        $stub = $this->files->get(substr(__DIR__,0, -5) . 'Stubs/service-interface.stub');
        $this->buildStub($this->scaffoldCommandObj->getMeta(), $stub);

        return $stub;
    }
}