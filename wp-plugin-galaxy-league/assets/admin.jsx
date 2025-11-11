import React, { useEffect, useState } from 'react'
import { createRoot } from 'react-dom/client'

const api = async (path, opt = {}) => {
  const res = await fetch(GLR.rest + path, {
    headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': GLR.nonce },
    credentials: 'same-origin',
    ...opt
  })
  const text = await res.text()
  let data
  try {
    data = text ? JSON.parse(text) : {}
  } catch (err) {
    throw new Error(`HTTP ${res.status}`)
  }
  if (!res.ok || data?.ok === false || data?.success === false) {
    const msg = data?.error || data?.message || data?.data?.message
    throw new Error(msg || `HTTP ${res.status}`)
  }
  return data
}

function ScoresApp(){
  const [seasons,setSeasons]=useState([])
  const [seasonId,setSeasonId]=useState('')
  const [matchDays,setMatchDays]=useState([])
  const [matchDayId,setMatchDayId]=useState('')
  const [fixtures,setFixtures]=useState([])
  const [fixtureId,setFixtureId]=useState('')
  const [participants,setParticipants]=useState({rows:[],match_day_id:null})
  const [lines,setLines]=useState([])
  const [pending,setPending]=useState([])
  const [absent,setAbsent]=useState({left:false,right:false})

  useEffect(()=>{ api('seasons').then(j=>setSeasons(j.rows||[])).catch(err=>alert(err.message)) },[])
  useEffect(()=>{ if(!seasonId) return; api(`match-days?season_id=${seasonId}`).then(j=>setMatchDays(j.rows||[])).catch(err=>alert(err.message)) },[seasonId])
  useEffect(()=>{ if(!matchDayId) return; api(`fixtures?match_day_id=${matchDayId}`).then(j=>setFixtures(j.rows||[])).catch(err=>alert(err.message)) },[matchDayId])

  useEffect(()=>{
    if(!fixtureId) return
    api(`participants?fixture_id=${fixtureId}`).then(j=>{
      const rows = Array.isArray(j.rows) ? j.rows : []
      setParticipants({
        rows,
        match_day_id: j.match_day_id ? Number(j.match_day_id) : null
      })
      const seeded=[]
      if(rows.length){
        [1,2,3].forEach(g=>{
          ['left','right'].forEach(side=>{
            [1,2,3].forEach(slot=>{
              const row=rows.find(r=>r.team_side===side && r.slot===slot)
              if(row){
                seeded.push({
                  fixture_id: Number(fixtureId),
                  game_number: g,
                  team_side: side,
                  player_slot: slot,
                  player_id: row.player_id,
                  scratch: 0,
                  is_blind: 0
                })
              }
            })
          })
        })
      }
      setLines(seeded)
      const fx=fixtures.find(f=>String(f.id)===String(fixtureId))
      setAbsent({left:!!fx?.left_side_absent,right:!!fx?.right_side_absent})
    }).catch(err=>alert(err.message))
  },[fixtureId,fixtures])

  const updateScratch=(g,side,slot,val)=>setLines(prev=>prev.map(L=>L.game_number===g&&L.team_side===side&&L.player_slot===slot?{...L,scratch:Number(val||0)}:L))
  const toggleBlind=(side,slot,checked)=>setLines(prev=>prev.map(L=>L.team_side===side&&L.player_slot===slot?{...L,is_blind:checked?1:0}:L))

  const saveAbsence=async()=>{
    try{
      const j=await api('fixture-absence',{method:'POST',body:JSON.stringify({fixture_id:Number(fixtureId),left_side_absent:absent.left,right_side_absent:absent.right})})
      if(!j.ok) alert('Error: '+(j.error||'unknown'))
    }catch(err){
      alert(err.message)
    }
  }

  const submit=async()=>{
    const payload={fixture_id:Number(fixtureId),match_day_id:Number(participants.match_day_id),lines}
    try{
      const j=await api('submit-scores',{method:'POST',body:JSON.stringify(payload)})
      setPending(j.result?.rolloffs_pending||[])
      alert('Saved')
    }catch(err){
      alert(err.message)
    }
  }

  const recompute=async()=>{
    try{
      const j=await api('recompute',{method:'POST',body:JSON.stringify({fixture_id:Number(fixtureId)})})
      setPending(j.result?.rolloffs_pending||[])
    }catch(err){
      alert(err.message)
    }
  }

  return (
    <div style={{maxWidth:1040}}>
      <div style={grid3}>
        <div><label>Season</label><select value={seasonId} onChange={e=>setSeasonId(e.target.value)} style={sel}><option value=''>—</option>{seasons.map(s=><option key={s.id} value={s.id}>{s.name} ({s.year})</option>)}</select></div>
        <div><label>Match Day</label><select value={matchDayId} onChange={e=>setMatchDayId(e.target.value)} style={sel}><option value=''>—</option>{matchDays.map(md=><option key={md.id} value={md.id}>{md.idx} — {md.label||md.date}</option>)}</select></div>
        <div><label>Fixture</label><select value={fixtureId} onChange={e=>setFixtureId(e.target.value)} style={sel}><option value=''>—</option>{fixtures.map(f=><option key={f.id} value={f.id}>{f.left_team} vs {f.right_team}</option>)}</select></div>
      </div>

      {fixtureId && (
        <div style={{margin:'12px 0'}}>
          <label><input type='checkbox' checked={absent.left} onChange={e=>setAbsent(a=>({...a,left:e.target.checked}))}/> Left Absent</label>
          {' '}|{' '}
          <label><input type='checkbox' checked={absent.right} onChange={e=>setAbsent(a=>({...a,right:e.target.checked}))}/> Right Absent</label>
          {' '}<button className='button' onClick={saveAbsence}>Save Absence</button>
        </div>
      )}

      {fixtureId && participants.rows?.length===0 && (
        <div style={{margin:'16px 0',padding:12,border:'1px solid #f0ad4e',borderRadius:8,background:'#fff8e5'}}>
          Δεν βρέθηκαν παίκτες για το fixture. Έλεγξε ότι έχεις αποθηκεύσει τη σειρά παικτών στο Setup → Order 1ης.
        </div>
      )}

      {participants.rows?.length>0 && (
        <div style={grid2}>
          {['left','right'].map(side=>(
            <div key={side} style={card}>
              <h3 style={{marginTop:0,textTransform:'capitalize'}}>{side}</h3>
              <table style={{width:'100%',borderCollapse:'collapse'}}>
                <thead><tr><th style={th}>Slot</th><th style={th}>Παίκτης</th><th style={th}>Blind</th><th style={th}>G1</th><th style={th}>G2</th><th style={th}>G3</th></tr></thead>
                <tbody>
                  {[1,2,3].map(slot=>{
                    const row=participants.rows.find(r=>r.team_side===side && r.slot===slot)
                    return (
                      <tr key={slot}>
                        <td style={td}>{slot}</td>
                        <td style={td}>{row?.full_name||'-'}</td>
                        <td style={td}><input type='checkbox' onChange={e=>toggleBlind(side,slot,e.target.checked)} /></td>
                        {[1,2,3].map(g=>{
                          const L=lines.find(x=>x.game_number===g && x.team_side===side && x.player_slot===slot)
                          return <td style={td} key={g}><input type='number' min='0' value={L?.scratch||0} onChange={e=>updateScratch(g,side,slot,e.target.value)} style={{width:80}}/></td>
                        })}
                      </tr>
                    )
                  })}
                </tbody>
              </table>
            </div>
          ))}
        </div>
      )}

      <div style={{marginTop:12}}>
        <button className='button button-primary' onClick={submit}>Αποθήκευση & Υπολογισμός</button>
        {' '}<button className='button' onClick={recompute}>Recompute</button>
      </div>

      {pending.length>0 && (
        <div style={{marginTop:20, border:'1px solid #ddd', borderRadius:8, padding:12}}>
          <h3>Pending Roll-offs</h3>
          {pending.map((p,idx)=> <RolloffForm key={idx} fixtureId={fixtureId} pending={p} /> )}
        </div>
      )}
    </div>
  )
}

function RolloffForm({fixtureId,pending}){
  const [L,setL]=useState(0), [R,setR]=useState(0)
  const winner = L>R?'left':(R>L?'right':null)
  const label = pending.scope==='series' ? 'Series roll-off' :
                pending.scope==='game'   ? `Game ${pending.game_number} roll-off` :
                `H2H G${pending.game_number} (L${pending.left_slot} vs R${pending.right_slot})`
  const save=async()=>{
    if(!winner) return alert('Το roll-off δεν μπορεί να είναι ισόπαλο.')
    const payload={fixture_id:Number(fixtureId),scope:pending.scope,game_number:pending.game_number??null,left_slot:pending.left_slot??null,right_slot:pending.right_slot??null,left_score:Number(L),right_score:Number(R),winner_side:winner}
    try{
      const j=await api('rolloffs',{method:'POST',body:JSON.stringify(payload)})
      if(!j.ok) alert('Error: '+(j.error||'unknown'))
    }catch(err){
      alert(err.message)
    }
  }
  return (
    <div style={{display:'flex',alignItems:'center',gap:8,marginBottom:8}}>
      <div style={{minWidth:280}}>{label}</div>
      <input type='number' value={L} onChange={e=>setL(e.target.value)} placeholder='Left' style={{width:90}} />
      <span>—</span>
      <input type='number' value={R} onChange={e=>setR(e.target.value)} placeholder='Right' style={{width:90}} />
      <button className='button' onClick={save}>Save</button>
    </div>
  )
}

const grid3={display:'grid',gridTemplateColumns:'1fr 1fr 1fr',gap:12,margin:'12px 0'}
const grid2={display:'grid',gridTemplateColumns:'1fr 1fr',gap:16}
const sel={width:'100%',padding:'6px'}
const card={border:'1px solid #ddd',borderRadius:8,padding:12}
const th={borderBottom:'1px solid #ddd',textAlign:'left',padding:'6px',fontWeight:700}
const td={borderBottom:'1px solid #f0f0f0',padding:'6px'}

const root = document.getElementById('glr-scores-root')
if (root) createRoot(root).render(<ScoresApp/>)

