import { useEffect, useState } from 'react';
import { AlertCircle, CheckCircle, AlertTriangle, Activity } from 'lucide-react';
import { api } from '../utils/api';

const severityIcons = {
  sehat: CheckCircle,
  waspada: AlertTriangle,
  bahaya: AlertCircle,
};

const severityColors = {
  sehat: { bg: 'bg-green-50', border: 'border-green-200', text: 'text-green-700', badge: 'bg-green-100 text-green-700', icon: 'text-green-500' },
  waspada: { bg: 'bg-amber-50', border: 'border-amber-200', text: 'text-amber-700', badge: 'bg-amber-100 text-amber-700', icon: 'text-amber-500' },
  bahaya: { bg: 'bg-red-50', border: 'border-red-200', text: 'text-red-700', badge: 'bg-red-100 text-red-700', icon: 'text-red-500' },
};

export function HealthIndicator({ td_sistol, td_diastol, imt, nadi, rr, disabilitas, gula_darah, kolesterol, hemoglobin, spo2, suhu_tubuh, usia, jenis_kelamin, compact, status }) {
  const [result, setResult] = useState(null);

  useEffect(() => {
    if (status) {
      setResult(status);
      return;
    }
    const hasData = td_sistol > 0 || td_diastol > 0 || imt > 0 || nadi > 0 || rr > 0 || disabilitas || gula_darah > 0 || kolesterol > 0 || hemoglobin > 0 || spo2 > 0 || suhu_tubuh > 0;
    if (hasData) {
      api.getHealthClassify({ td_sistol: parseInt(td_sistol) || 0, td_diastol: parseInt(td_diastol) || 0, imt: parseFloat(imt) || 0, nadi: parseInt(nadi) || 0, rr: parseInt(rr) || 0, disabilitas, gula_darah: parseInt(gula_darah) || 0, kolesterol: parseInt(kolesterol) || 0, hemoglobin: parseFloat(hemoglobin) || 0, spo2: parseInt(spo2) || 0, suhu_tubuh: parseFloat(suhu_tubuh) || 0, usia: parseInt(usia) || 0, jenis_kelamin })
        .then(res => setResult(res.data))
        .catch(() => setResult(null));
    } else {
      setResult(null);
    }
  }, [td_sistol, td_diastol, imt, nadi, rr, disabilitas, gula_darah, kolesterol, hemoglobin, spo2, suhu_tubuh, usia, jenis_kelamin, status]);

  if (!result) return null;

  const colors = severityColors[result.status];
  const Icon = severityIcons[result.status];

  if (compact) {
    return (
      <div className={`inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium border ${colors.badge}`}>
        <Icon className={`w-3 h-3 ${colors.icon}`} />
        {result.label}
      </div>
    );
  }

  return (
    <div className={`rounded-xl border p-4 ${colors.bg} ${colors.border}`}>
      <div className="flex items-center gap-3 mb-3">
        <div className={`w-10 h-10 rounded-full flex items-center justify-center ${colors.badge}`}>
          <Icon className={`w-5 h-5 ${colors.icon}`} />
        </div>
        <div>
          <p className={`font-semibold ${colors.text}`}>Status: {result.label}</p>
          {usia > 0 && <p className="text-xs text-gray-500">Kategori usia: {usia >= 70 ? 'Lansia Ristik (≥70 th)' : usia >= 60 ? 'Lansia (60-69 th)' : 'Pra-Lansia (<60 th)'}</p>}
        </div>
      </div>

      {result.issues.length > 0 && (
        <div className="space-y-2 mb-3">
          {result.issues.map((issue, i) => {
            const issueIcon = issue.severity === 'bahaya' ? AlertCircle : AlertTriangle;
            const issueColor = issue.severity === 'bahaya' ? 'text-red-600' : 'text-amber-600';
            return (
              <div key={i} className="flex items-start gap-2 text-sm">
                <issueIcon className={`w-4 h-4 mt-0.5 flex-shrink-0 ${issueColor}`} />
                <div>
                  <span className="font-medium">{issue.parameter}:</span>{' '}
                  <span className={issueColor}>{issue.value}</span>
                  <span className="text-gray-500 ml-1">({issue.category})</span>
                </div>
              </div>
            );
          })}
        </div>
      )}

      {result.issues.length === 0 && (
        <div className="flex items-center gap-2 text-sm text-green-600 mb-3">
          <CheckCircle className="w-4 h-4" />
          Semua parameter dalam batas normal
        </div>
      )}

      <div className="text-xs text-gray-500 border-t pt-2">
        <Activity className="w-3 h-3 inline mr-1" />
        Rekomendasi: {result.recommendation}
      </div>
    </div>
  );
}
