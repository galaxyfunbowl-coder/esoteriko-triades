import React, { useEffect, useState } from ''react''

export default function App({ view }) {
  return <Standings />
}

function Standings(){
  const [rows,setRows]=useState([]); const [err,setErr]=useState(null); const [loading,setLoading]=useState(true)
  useEffect(()=>{
    const f=new FormData(); f.append(''action'',''glr_get_standings''); f.append(''_ajax_nonce'',GLR?.nonce||'''')
    fetch(GLR.ajaxUrl,{method:''POST'',body:f}).then(r=>r.json()).then(j=>{
      if(j.success) setRows(j.data.rows||[]); else setErr(j.data?.message||''Error'')
    }).catch(e=>setErr(e.message)).finally(()=>setLoading(false))
  },[])
  if(loading) return <div>Φόρτωση...</div>
  if(err) return <div>Σφάλμα: {err}</div>
  return (
    <div style={{overflowX:''auto''}}>
      <table style={{width:''100%'',borderCollapse:''collapse''}}>
        <thead><tr><th style={th}>Θέση</th><th style={th}>Ομάδα</th><th style={th}>Πόντοι</th><th style={th}>Pins</th></tr></thead>
        <tbody>
          {rows.map((r,i)=>(
            <tr key={r.team_id}>
              <td style={td}>{i+1}</td>
              <td style={td}>{r.team_name}</td>
              <td style={td}>{Number(r.total_points??0)}</td>
              <td style={td}>{Number(r.total_pins??0)}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  )
}
const th={borderBottom:''1px solid #ddd'',textAlign:''left'',padding:''8px'',fontWeight:700}
const td={borderBottom:''1px solid #f0f0f0'',padding:''8px''}
