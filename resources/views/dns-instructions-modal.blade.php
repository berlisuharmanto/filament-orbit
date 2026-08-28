<div class="space-y-4 text-sm">
    <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
        <p class="font-medium text-gray-900 dark:text-gray-100">
            To connect <span class="font-mono font-bold text-primary-600 dark:text-primary-400">{{ $domain->domain }}</span>, add the following DNS record(s) at your domain registrar or DNS host:
        </p>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse border border-gray-200 dark:border-gray-700">
            <thead>
                <tr class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                    <th class="p-2 border border-gray-200 dark:border-gray-700">Type</th>
                    <th class="p-2 border border-gray-200 dark:border-gray-700">Host / Name</th>
                    <th class="p-2 border border-gray-200 dark:border-gray-700">Target / Value</th>
                    <th class="p-2 border border-gray-200 dark:border-gray-700">TTL</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($expected as $exp)
                    <tr class="border border-gray-200 dark:border-gray-700 font-mono text-xs">
                        <td class="p-2 font-bold text-primary-600 dark:text-primary-400">{{ $exp->type }}</td>
                        <td class="p-2">{{ count(explode('.', $domain->domain)) > 2 ? explode('.', $domain->domain)[0] : '@' }}</td>
                        <td class="p-2 select-all">{{ $exp->target }}</td>
                        <td class="p-2 text-gray-500">Auto (or 300)</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="p-3 bg-blue-50 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200 rounded border border-blue-200 dark:border-blue-800 text-xs">
        <strong>Note:</strong> DNS propagation can take anywhere from a few seconds up to 24 hours depending on TTL. You can click <strong>"Verify DNS"</strong> at any time to run an instant multi-resolver check.
    </div>
</div>
