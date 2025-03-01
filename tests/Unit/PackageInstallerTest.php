<?php

namespace LastCall\DownloadsPlugin\Tests\Unit;

use Composer\Composer;
use Composer\Installer\InstallationManager;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Package\RootPackageInterface;
use LastCall\DownloadsPlugin\Exception\UnexpectedValueException;
use LastCall\DownloadsPlugin\PackageInstaller;
use LastCall\DownloadsPlugin\Subpackage;
use LastCall\DownloadsPlugin\SubpackageFactory;
use LastCall\DownloadsPlugin\SubpackageInstaller;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PackageInstallerTest extends TestCase
{
    private SubpackageFactory|MockObject $factory;
    private SubpackageInstaller|MockObject $subpackageInstaller;
    private PackageInstaller $installer;
    private Composer|MockObject $composer;
    private IOInterface|MockObject $io;
    private PackageInterface|MockObject $package;
    private RootPackageInterface|MockObject $rootPackage;
    private InstallationManager|MockObject $installationManager;
    private array $extra = ['downloads' => ['file1', 'file2', 'file3']];
    private array $subpackages;

    protected function setUp(): void
    {
        $this->factory = $this->createMock(SubpackageFactory::class);
        $this->subpackageInstaller = $this->createMock(SubpackageInstaller::class);
        $this->installer = new PackageInstaller($this->factory, $this->subpackageInstaller);
        $this->composer = $this->createMock(Composer::class);
        $this->io = $this->createMock(IOInterface::class);
        $this->package = $this->createMock(PackageInterface::class);
        $this->rootPackage = $this->createMock(RootPackageInterface::class);
        $this->installationManager = $this->createMock(InstallationManager::class);
        $this->subpackages = [
            $this->createMock(Subpackage::class),
            $this->createMock(Subpackage::class),
            $this->createMock(Subpackage::class),
        ];
    }

    /**
     * @testWith [[]]
     *           [{"key": "value"}]
     *           [{"downloads": []}]
     */
    public function testInstallPackageWithoutExtraFiles(array $extra): void
    {
        $this->composer->expects($this->never())->method('getInstallationManager');
        $this->package->expects($this->once())->method('getExtra')->willReturn($extra);
        $this->installer->install($this->package, $this->composer, $this->io);
    }

    public function testInstallRootPackage(): void
    {
        $this->rootPackage->expects($this->once())->method('getExtra')->willReturn($this->extra);
        $this->rootPackage->expects($this->once())->method('getName')->willReturn('root/package-name');
        $this->composer->expects($this->never())->method('getInstallationManager');
        $this->installationManager->expects($this->never())->method('getInstallPath');
        $this->installationManager->expects($this->never())->method('ensureBinariesPresence');
        $this->factory
            ->expects($this->once())
            ->method('create')
            ->with($this->rootPackage, getcwd())
            ->willReturn($this->subpackages);
        $this->subpackageInstaller
            ->expects($this->exactly(\count($this->subpackages)))
            ->method('isInstalled')
            ->with($this->io)
            ->willReturnOnConsecutiveCalls(true, false, false);
        $this->subpackageInstaller
            ->expects($this->exactly(2))
            ->method('install')
            ->with($this->composer, $this->io);
        $this->io->expects($this->once())->method('write')->with('<info>Download extra files for <comment>root/package-name</comment></info>');
        $this->installer->install($this->rootPackage, $this->composer, $this->io);
    }

    public function testInstallNormalPackage(): void
    {
        $basePath = '/path/to/install/path';
        $this->package->expects($this->once())->method('getExtra')->willReturn($this->extra);
        $this->package->expects($this->once())->method('getName')->willReturn('normal/package-name');
        $this->composer->expects($this->exactly(2))->method('getInstallationManager')->willReturn($this->installationManager);
        $this->installationManager->expects($this->once())->method('getInstallPath')->with($this->package)->willReturn($basePath);
        $this->installationManager->expects($this->once())->method('ensureBinariesPresence')->with($this->package);
        $this->factory
            ->expects($this->once())
            ->method('create')
            ->with($this->package, $basePath)
            ->willReturn($this->subpackages);
        $this->subpackageInstaller
            ->expects($this->exactly(\count($this->subpackages)))
            ->method('isInstalled')
            ->with($this->io)
            ->willReturnOnConsecutiveCalls(true, false, false);
        $this->subpackageInstaller
            ->expects($this->exactly(2))
            ->method('install')
            ->with($this->composer, $this->io);
        $this->io->expects($this->once())->method('write')->with('<info>Download extra files for <comment>normal/package-name</comment></info>');
        $this->installer->install($this->package, $this->composer, $this->io);
    }

    public function testInstallRootPackageWithInvalidExtraFiles(): void
    {
        $this->rootPackage->expects($this->once())->method('getExtra')->willReturn($this->extra);
        $this->rootPackage->expects($this->once())->method('getName')->willReturn('root/package-name');
        $this->composer->expects($this->never())->method('getInstallationManager');
        $this->installationManager->expects($this->never())->method('getInstallPath');
        $this->installationManager->expects($this->never())->method('ensureBinariesPresence');
        $this->factory
            ->expects($this->once())
            ->method('create')
            ->with($this->rootPackage, getcwd())
            ->willThrowException(new UnexpectedValueException('Invalid extra files'));
        $this->subpackageInstaller->expects($this->never())->method('isInstalled');
        $this->subpackageInstaller->expects($this->never())->method('install');
        $this->io->expects($this->never())->method('write');
        $this->installer->install($this->rootPackage, $this->composer, $this->io);
    }

    public function testInstallNormalPackageWithInvalidExtraFiles(): void
    {
        $basePath = '/path/to/install/path';
        $this->package->expects($this->once())->method('getExtra')->willReturn($this->extra);
        $this->package->expects($this->once())->method('getName')->willReturn('normal/package-name');
        $this->composer->expects($this->exactly(2))->method('getInstallationManager')->willReturn($this->installationManager);
        $this->installationManager->expects($this->once())->method('getInstallPath')->with($this->package)->willReturn($basePath);
        $this->installationManager->expects($this->once())->method('ensureBinariesPresence')->with($this->package);
        $this->factory
            ->expects($this->once())
            ->method('create')
            ->with($this->package, $basePath)
            ->willThrowException(new UnexpectedValueException('Invalid extra files'));
        $this->subpackageInstaller->expects($this->never())->method('isInstalled');
        $this->subpackageInstaller->expects($this->never())->method('install');
        $this->io->expects($this->never())->method('write');
        $this->installer->install($this->package, $this->composer, $this->io);
    }
}
