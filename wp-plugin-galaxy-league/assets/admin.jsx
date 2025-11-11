import React, { useEffect, useState } from 'react'
import { createRoot } from 'react-dom/client'

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

  const jget=(p)=>fetch(`${GLR.rest}${p}`).then(r=>r.json())

  useEffect(()=>{ jget('seasons').then(j=>setSeasons(j.rows||[])) },[])
  useEffect(()=>{ if(!seasonId) return; jget(`match-days?season_id=${seasonId}`).then(j=>setMatchDays(j.rows||[])) },[seasonId])
  useEffect(()=>{ if(!matchDayId) return; jget(`fixtures?match_day_id=${matchDayId}`).then(j=>setFixtures(j.rows||[])) },[matchDayId])

  useEffect(()=>{
    if(!fixtureId) return
    jget(`participants?fixture_id=${fixtureId}`).then(j=>{
      setParticipants(j)
      const seeded=[]
      ;[1,2,3].forEach(g=>['left','right'].forEach(side=>[1,2,3].forEach(slot=>{
        const row=j.rows.find(r=>r.team_side===side && r.slot===slot)
        if(row) seeded.push({fixture_id:Number(fixtureId),game_number:g,team_side:side,player_slot:slot,player_id:row.player_id,scratch:0,is_blind:0})
      })))
      setLines(seeded)
      const fx=fixtures.find(f=>String(f.id)===String(fixtureId))
      setAbsent({left:!!fx?.left_side_absent,right:!!fx?.right_side_absent})
    })
  },[fixtureId])

  const updateScratch=(g,side,slot,val)=>setLines(prev=>prev.map(L=>L.game_number===g&&L.team_side===side&&L.player_slot===slot?{...L,scratch:Number(val||0)}:L))
  const toggleBlind=(side,slot,checked)=>setLines(prev=>prev.map(L=>L.team_side===side&&L.player_slot===slot?{...L,is_blind:checked?1:0}:L))

  const saveAbsence=async()=>{
    const res=await fetch(`${GLR.rest}fixture-absence`,{method:'POST',headers:{'Content-Type':'application/json','X-WP-Nonce':GLR.nonce},body:JSON.stringify({fixture_id:Number(fixtureId),left_side_absent:absent.left,right_side_absent:absent.right})})
    const j=await res.json(); if(!j.ok) alert('Error: '+j.error)
  }

  const submit=async()=>{
    const payload={fixture_id:Number(fixtureId),match_day_id:Number(participants.match_day_id),lines}
    const res=await fetch(`${GLR.rest}submit-scores`,{method:'POST',headers:{'Content-Type':'application/json','X-WP-Nonce':GLR.nonce},body:JSON.stringify(payload)})
    const j=await res.json()
    if(j.ok){ setPending(j.result?.rolloffs_pending||[]); alert('Saved') } else alert('Error: '+j.error)
  }

  const recompute=async()=>{
    const r=await fetch(`${GLR.rest}recompute`,{method:'POST',headers:{'Content-Type':'application/json','X-WP-Nonce':GLR.nonce},body:JSON.stringify({fixture_id:Number(fixtureId)})})
    const j=await r.json(); setPending(j.result?.rolloffs_pending||[])
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
    const r=await fetch(`${GLR.rest}rolloffs`,{method:'POST',headers:{'Content-Type':'application/json','X-WP-Nonce':GLR.nonce},body:JSON.stringify(payload)})
    const j=await r.json(); if(!j.ok) alert('Error: '+j.error)
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

