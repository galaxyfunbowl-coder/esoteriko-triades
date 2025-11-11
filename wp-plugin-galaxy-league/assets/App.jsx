import React, { useEffect, useState } from 'react'

export default function App({ view }) {
  if (view === 'standings') return <Standings />
  return <div>Galaxy League</div>
}

function Standings() {
  const [rows, setRows] = useState([])
  const [error, setError] = useState(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    const form = new FormData()
    form.append('action', 'glr_get_standings')
    form.append('_ajax_nonce', (window.GLR && GLR.nonce) || '')

    fetch(GLR.ajaxUrl, { method: 'POST', body: form })
      .then(r => r.json())
      .then(json => {
        if (json.success) setRows(json.data.rows || [])
        else setError(json.data?.message || 'Error')
      })
      .catch(e => setError(e.message))
      .finally(() => setLoading(false))
  }, [])

  if (loading) return <div>Φόρτωση...</div>
  if (error) return <div>Σφάλμα: {error}</div>

  return (
    <div style={{overflowX:'auto'}}>
      <table style={{width:'100%', borderCollapse:'collapse'}}>
        <thead>
          <tr>
            <th style={th}>Θέση</th>
            <th style={th}>Ομάδα</th>
            <th style={th}>Πόντοι</th>
            <th style={th}>Pins</th>
          </tr>
        </thead>
        <tbody>
          {rows.map((r, i) => (
            <tr key={r.team_id} style={tr}>
              <td style={td}>{i+1}</td>
              <td style={td}>{r.team_name}</td>
              <td style={td}>{Number(r.total_points ?? 0)}</td>
              <td style={td}>{Number(r.total_pins ?? 0)}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  )
}

const th = { borderBottom:'1px solid #ddd', textAlign:'left', padding:'8px', fontWeight:'700' }
const td = { borderBottom:'1px solid #f0f0f0', padding:'8px' }
const tr = {}
