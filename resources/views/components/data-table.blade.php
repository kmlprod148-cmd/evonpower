<table class="min-w-full divide-y divide-gray-200">
    <thead class="bg-gray-50">
        <tr>
            @foreach($headers as $header)
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    {{ $header }}
                </th>
            @endforeach
        </tr>
    </thead>
    <tbody class="bg-white divide-y divide-gray-200">
        @foreach($rows as $row)
            <tr>
                @foreach($row as $cell)
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $cell }}
                    </td>
                @endforeach
            </tr>
        @endforeach
    </tbody>
</table>