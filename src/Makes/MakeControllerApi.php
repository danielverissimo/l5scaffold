<?php
namespace Laralib\L5scaffold\Makes;

use Illuminate\Console\DetectsApplicationNamespace;
use Illuminate\Filesystem\Filesystem;
use Laralib\L5scaffold\Commands\ScaffoldMakeCommand;
use Laralib\L5scaffold\Validators\SchemaParser as ValidatorParser;
use Laralib\L5scaffold\Validators\SyntaxBuilder as ValidatorSyntax;


class MakeControllerApi
{
    use DetectsApplicationNamespace, MakerTrait;

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

        $this->start();
    }

    /**
     * Start make controller.
     *
     * @return void
     */
    private function start()
    {
        $name = $this->scaffoldCommandObj->getObjName('Name') . 'Controller';
        $path = $this->getPath($name, 'controller-api');

        if ($this->files->exists($path))
        {
            return $this->scaffoldCommandObj->comment("x $name");
        }

        $this->makeDirectory($path);

        $this->files->put($path, $this->compileControllerStub());

        $this->scaffoldCommandObj->info('+ API Controller');
    }

    /**
     * Compile the controller stub.
     *
     * @return string
     */
    protected function compileControllerStub()
    {
        $stub = $this->files->get(substr(__DIR__,0, -5) . 'Stubs/controller-api.stub');

        $this->buildStub($this->scaffoldCommandObj->getMeta(), $stub);
        // $this->replaceValidator($stub);

        return $stub;
    }
}