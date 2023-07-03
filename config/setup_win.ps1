#Create configuration files from conf template files
#Written by Greg Post - Symbiota Support Hub
#symbiota@asu.edu

#command line parameters
param ($writeover = $false)

Write-Output 'Checking if this script is running in the Symbiota config folder..'

$curDir = Get-Location

$myParent = (get-item $curDir).Parent.FullName
if($myParent -NotLike '*\Symbiota'){
	Write-Output "Current Directory: $curDir"
	Write-Output 'Please execute this script from the Symbiota\config folder'
	Start-Sleep -Seconds 5
	Exit
}

Write-Output 'Success...'

#handle overwrite flag

if ($writeover -eq $true){
	Write-Output 'Warning! Overwrite flag detected - existing files will be overwritten by templates'
	$confirm_overwrite = Read-Host -Prompt 'Are you sure you want to overwrite from templates (y/n):'
	if($confirm_overwrite -eq 'n'){ 
		Write-Output 'Action cancelled.'
		Exit
	}
}
else {
	Write-Output 'Overwrite flag was NOT detected. Pre-existing files will NOT be over written'
	Start-Sleep -Seconds 3
}#else


# $template_dirs contains the list of directories that will be searched for *_template.php files that will be copied and renamed *.php
$template_dirs = 
	'\config',
	'',
	'\includes',
	'\content\lang'

Foreach($i in $template_dirs){				

	$curDir = $myParent + $i
	Write-Output ''
	Write-Output "creating files from templates in $curDir"

	if ($writeover -ne $true){
		Get-ChildItem -Path $curDir -Filter '*_template.php' | ForEach-Object {
			$NewName = $_.FullName -replace '_template.php$', '.php'
			Copy-Item -Path $_.FullName -Destination $NewName
			Write-Output "Created $NewName"
		}
	}#if
	else{
		Get-ChildItem -Path $curDir -Filter '*_template.php' -Force | ForEach-Object {
			$NewName = $_.FullName -replace '_template.php$', '.php'
			Copy-Item -Path $_.FullName -Destination $NewName
			Write-Output "Created $NewName"
		}
	}#else
}

Write-Output 'Symbiota template set up complete'
Start-Sleep -Seconds 20
Exit
