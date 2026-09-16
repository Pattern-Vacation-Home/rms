<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class TtlockClient
{
    public function configured(): bool
    {
        return filled(AppSettings::get('ttlock_client_id')) && filled(AppSettings::get('ttlock_client_secret'))
            && filled(AppSettings::get('ttlock_access_token'));
    }

    public function locks(): array
    {
        $locks = [];
        for ($page = 1; $page <= 100; $page++) {
            $result = $this->request('/v3/lock/list', ['pageNo' => $page, 'pageSize' => 100]);
            $batch = $result['list'] ?? [];
            if (! is_array($batch)) throw new RuntimeException('TTLock returned an invalid lock list.');
            $locks = array_merge($locks, $batch);
            if (count($batch) < 100) break;
        }

        return $locks;
    }

    public function addPasscode(int $lockId, string $passcode, int $startMillis, int $endMillis, string $name): int
    {
        $result = $this->request('/v3/keyboardPwd/add', [
            'lockId' => $lockId, 'keyboardPwd' => $passcode, 'keyboardPwdName' => $name,
            'startDate' => $startMillis, 'endDate' => $endMillis, 'addType' => 2,
        ]);
        if (! isset($result['keyboardPwdId'])) throw new RuntimeException('TTLock did not confirm the new passcode.');

        return (int) $result['keyboardPwdId'];
    }

    public function deletePasscode(int $lockId, int $passcodeId): void
    {
        $this->request('/v3/keyboardPwd/delete', ['lockId' => $lockId, 'keyboardPwdId' => $passcodeId, 'deleteType' => 2]);
    }

    private function request(string $path, array $params): array
    {
        if (! $this->configured()) throw new RuntimeException('TTLock connection is not configured.');
        $base = rtrim((string) config('hhms.ttlock_api_base'), '/');
        if (! in_array($base, ['https://api.sciener.com', 'https://api.ttlock.com', 'https://euapi.ttlock.com'], true)) {
            throw new RuntimeException('TTLock API host is not allowed.');
        }
        $response = Http::timeout(20)->retry(2, 500)->asForm()->post($base.$path, array_merge($params, [
            'clientId' => AppSettings::get('ttlock_client_id'),
            'accessToken' => AppSettings::get('ttlock_access_token'),
            'date' => now()->getTimestampMs(),
        ]));
        if (! $response->successful() || ! is_array($response->json())) {
            throw new RuntimeException('TTLock is unavailable. Try again later.');
        }
        $data = $response->json();
        if ((int) ($data['errcode'] ?? 0) !== 0) {
            Log::warning('TTLock API error', ['path' => $path, 'code' => $data['errcode']]);
            throw new RuntimeException('TTLock rejected the request (code '.$data['errcode'].'). Check the connection and lock capabilities.');
        }

        return $data;
    }
}
