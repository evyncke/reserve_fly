<?php

/*
   Copyright 2026-2026 Eric Vyncke

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.

*/

// MCP Agent API - provides access to bookings, users, invoices, folios and students
// Authentication: require $userIsMember != 0

// Include existing app bootstrap (dbi.php sets Joomla user context)
require_once 'dbi.php';
MustBeLoggedIn();

if (! $userIsBoardMember) {
    http_response_code(403) ;
    echo "Forbidden - reserved for board members only" ;
    exit ;
}

require_once 'mobile_header5.php' ;

$scan_root = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..') ;
$modified_after = time() - (48 * 60 * 60) ;
$files = [] ;

if ($scan_root !== false) {
        try {
                $directory_iterator = new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator(
                                $scan_root,
                                FilesystemIterator::SKIP_DOTS | FilesystemIterator::CURRENT_AS_FILEINFO
                        ),
                        RecursiveIteratorIterator::LEAVES_ONLY
                ) ;

                foreach ($directory_iterator as $file_info) {
                        if (! $file_info->isFile()) {
                                continue ;
                        }

                        $modification_time = $file_info->getMTime() ;
                        if ($file_info->getBasename() !== '.htaccess' and ! preg_match('/^[0-9a-f]{10,12}\.php$/i', $file_info->getBasename()) and $modification_time < $modified_after) { # also f(preg_match('/^[0-9a-f]{12}\.php$/i',basename($file)))
                                continue ;
                        }

                        $files[] = [
                                'path' => $file_info->getPathname(),
                                'size' => $file_info->getSize(),
                                'modified' => $modification_time,
                        ] ;
                }
        } catch (UnexpectedValueException $exception) {
                $scan_error = $exception->getMessage() ;
        }
}

usort($files, function (array $left, array $right): int {
        return $right['modified'] <=> $left['modified'] ;
}) ;

function format_file_size(int $size): string {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'] ;
        $unit = 0 ;
        $display_size = (float) $size ;

        while ($display_size >= 1024 and $unit < count($units) - 1) {
                $display_size /= 1024 ;
                $unit++ ;
        }

        return number_format($display_size, $unit === 0 ? 0 : 1, '.', '') . ' ' . $units[$unit] ;
}

?>
<main class="container-fluid py-3">
    <h2>Information à propos d'un piratage éventuel</h2>
    <lead>Cette page est réservée aux administrateurs et comptables du site. Elle permet de vérifier si des fichiers 
        ont été modifiés récemment ou si des tentatives de piratage ont été détectées. 
        Il est normal de voir des fichiers modifiés récemment, mais il est important de surveiller les modifications suspectes.
        En clair, c'est pour Éric et Patrick ;-)</lead>
    <h3 class="h3 mb-3">Recent, weird hex filenames,  and .htaccess files</h3>
    <?php if (isset($scan_error)) { ?>
        <div class="alert alert-warning" role="alert">
            The directory scan could not be completed: <?=htmlspecialchars($scan_error, ENT_QUOTES, 'UTF-8')?>
        </div>
    <?php } ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th scope="col">Full path</th>
                    <th scope="col" class="text-end">Size</th>
                    <th scope="col">Last modification</th>
                </tr>
            </thead>
            <tbody>
<?php if (count($files) === 0) { ?>
                <tr>
                    <td colspan="3" class="text-center text-muted">No matching files found.</td>
                </tr>
<?php } else {
        foreach ($files as $file) { ?>
                <tr>
                    <td class="font-monospace text-break"><?=htmlspecialchars($file['path'], ENT_QUOTES, 'UTF-8')?></td>
                    <td class="text-end text-nowrap"><?=format_file_size($file['size'])?></td>
                    <td class="text-nowrap"><?=htmlspecialchars(date('Y-m-d H:i:s T', $file['modified']), ENT_QUOTES, 'UTF-8')?></td>
                </tr>
<?php 
}
} 
?>
            </tbody>
        </table>
    </div><!-- table-responsive -->
<?php
$audit_log_path = rtrim($_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'hack_request_audit.log' ;
$audit_entries = [] ;

if (is_readable($audit_log_path)) {
        $audit_lines = file($audit_log_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ;
        if ($audit_lines !== false) {
                foreach ($audit_lines as $audit_line) {
                        $audit_entry = json_decode($audit_line, true) ;
                        if (is_array($audit_entry)) {
                                $audit_entries[] = $audit_entry ;
                        }
                }
        }
}
?>
        <h3 class="h3 mb-3">Hack request audit log</h3>
        <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                        <thead class="table-dark">
                                <tr>
                                        <th scope="col">Datetime</th>
                                        <th scope="col">Trojan</th>
                                        <th scope="col">Client IP</th>
                                        <th scope="col">Evaluated command</th>
                                        <th scope="col">File name</th>
                                </tr>
                        </thead>
                        <tbody>
<?php if (count($audit_entries) === 0) { ?>
                                <tr>
                                        <td colspan="4" class="text-center text-muted">No audit entries found.</td>
                                </tr>
<?php } else {
                foreach (array_reverse($audit_entries) as $audit_entry) { ?>
                                <tr>
                                        <td class="text-nowrap"><?=htmlspecialchars((string) ($audit_entry['datetime'] ?? ''), ENT_QUOTES, 'UTF-8')?></td>
                                        <td class="text-nowrap"><?=htmlspecialchars((string) ($audit_entry['trojan'] ?? ''), ENT_QUOTES, 'UTF-8')?></td>
                                        <td class="text-nowrap"><?=htmlspecialchars((string) ($audit_entry['client_ip'] ?? ''), ENT_QUOTES, 'UTF-8')?></td>
                                        <td class="font-monospace text-break"><?=htmlspecialchars((string) ($audit_entry['eval_cmd'] ?? ''), ENT_QUOTES, 'UTF-8')?></td>
                                        <td class="font-monospace text-break"><?=htmlspecialchars((string) ($audit_entry['file_name'] ?? ''), ENT_QUOTES, 'UTF-8')?><br/>
                                            <?=htmlspecialchars((string) ($audit_entry['file_tmp_name'] ?? ''), ENT_QUOTES, 'UTF-8')?></td>
                                </tr>
<?php }
} ?>
                        </tbody>
                </table>
        </div><!-- table-responsive -->


</main>
</html>