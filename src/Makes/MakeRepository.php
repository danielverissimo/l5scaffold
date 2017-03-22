<?php
namespace Laralib\L5scaffold\Makes;

use Illuminate\Console\DetectsApplicationNamespace;
use Illuminate\Filesystem\Filesystem;
use Laralib\L5scaffold\Commands\ScaffoldMakeCommand;
use Laralib\L5scaffold\Validators\SchemaParser as ValidatorParser;
use Laralib\L5scaffold\Validators\SyntaxBuilder as ValidatorSyntax;


class MakeRepository
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

        $this->createRepository();
        $this->createRepositoryInterface();
    }

    /**
     * Start make repository.
     *
     * @return void
     */
    private function createRepository()
    {
        $name = $this->scaffoldCommandObj->getObjName('Name') . 'Repository';
        $path = $this->getPath($name, 'repository');


        if ($this->files->exists($path)) 
        {
            return $this->scaffoldCommandObj->comment("x $name");
        }

        $this->makeDirectory($path);

        $this->files->put($path, $this->compileRepositoryStub());

        $this->scaffoldCommandObj->info('+ Repository');
    }

    /**
     * Start make repository interface.
     *
     * @return void
     */
    private function createRepositoryInterface()
    {
        $name = $this->scaffoldCommandObj->getObjName('Name') . 'RepositoryInterface';
        $path = $this->getPath($name, 'repository');


        if ($this->files->exists($path))
        {
            return $this->scaffoldCommandObj->comment("x $name");
        }

        $this->makeDirectory($path);

        $this->files->put($path, $this->compileRepositoryInterfaceStub());

        $this->scaffoldCommandObj->info('+ Repository Interface');
    }

    /**
     * Compile the repository stub.
     *
     * @return string
     */
    protected function compileRepositoryStub()
    {
        $stub = $this->files->get(substr(__DIR__,0, -5) . 'Stubs/repository.stub');
        $this->buildStub($this->scaffoldCommandObj->getMeta(), $stub);

        return $stub;
    }

    /**
     * Compile the repository stub.
     *
     * @return string
     */
    protected function compileRepositoryInterfaceStub()
    {
        $stub = $this->files->get(substr(__DIR__,0, -5) . 'Stubs/repository-interface.stub');
        $this->buildStub($this->scaffoldCommandObj->getMeta(), $stub);

        return $stub;
    }
}