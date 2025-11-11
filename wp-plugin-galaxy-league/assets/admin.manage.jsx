import React, { useEffect, useState } from 'react'
import { createRoot } from 'react-dom/client'

const api = async (p, opt={}) => {
  const res = await fetch(GLR.rest + p, {
    headers:{ 'Content-Type':'application/json', 'X-WP-Nonce': GLR.nonce }, ...opt
  })
  const text = await res.text()
  let data
  try {
    data = text ? JSON.parse(text) : {}
  } catch (err) {
    throw new Error(`HTTP ${res.status}`)
  }
  if (!res.ok || (data && data.ok === false) || data?.success === false) {
    const msg = data?.error || data?.message || data?.data?.message
    throw new Error(msg || `HTTP ${res.status}`)
  }
  return data
}

function ManageApp(){
  const [tab,setTab]=useState('seasons')
  return (
    <div style={{maxWidth:1100}}>
      <div style={{display:'flex',gap:8,margin:'12px 0'}}>
        {['seasons','teams','fixtures'].map(t =>
          <button key={t} className={'button'+(tab===t?' button-primary':'')} onClick={()=>setTab(t)}>
            {t==='seasons'?'Seasons & Match Days':t==='teams'?'Teams & Players':'Program (Fixtures)'}
          </button>
        )}
      </div>
      {tab==='seasons' && <SeasonsTab/>}
      {tab==='teams' && <TeamsTab/>}
      {tab==='fixtures' && <FixturesTab/>}
    </div>
  )
}

function SeasonsTab(){
  const [seasons,setSeasons]=useState([]); const [form,setForm]=useState({name:'',year:new Date().getFullYear(),total_match_days:7})
  const refresh=()=>api('seasons').then(j=>setSeasons(j.rows||[])).catch(console.error)
  useEffect(()=>{refresh()},[])
  const save=async()=>{
    try {
      await api('seasons',{method:'POST',body:JSON.stringify(form)})
      setForm({...form,name:''})
      refresh()
    } catch (err) {
      alert(err.message)
    }
  }
  return (
    <>
      <h2>Seasons</h2>
      <div style={row}>
        <input placeholder='Name' value={form.name} onChange={e=>setForm({...form,name:e.target.value})}/>
        <input type='number' placeholder='Year' value={form.year} onChange={e=>setForm({...form,year:Number(e.target.value)})}/>
        <input type='number' placeholder='Match days' value={form.total_match_days} onChange={e=>setForm({...form,total_match_days:Number(e.target.value)})}/>
        <button className='button' onClick={save}>Add</button>
      </div>
      <table style={tbl}><thead><tr><th>ID</th><th>Name</th><th>Year</th><th>MDs</th></tr></thead>
      <tbody>{seasons.map(s=><tr key={s.id}><td>{s.id}</td><td>{s.name}</td><td>{s.year}</td><td>{s.total_match_days}</td></tr>)}</tbody></table>
      <MatchDaysEditor seasons={seasons}/>
    </>
  )
}
function MatchDaysEditor({seasons}){
  const [seasonId,setSeasonId]=useState(''); const [rows,setRows]=useState([]); const [form,setForm]=useState({idx:1,label:'',date:'',type:'regular'})
  useEffect(()=>{ if(seasonId) api(`match-days?season_id=${seasonId}`).then(j=>setRows(j.rows||[])).catch(console.error) },[seasonId])
  const reload=()=>{ if(seasonId) api(`match-days?season_id=${seasonId}`).then(j=>setRows(j.rows||[])).catch(console.error) }
  const add=async()=>{
    try {
      await api('match-days',{method:'POST',body:JSON.stringify({...form,season_id:Number(seasonId)})})
      reload()
    } catch (err) {
      alert(err.message)
    }
  }
  const del=async(id)=>{
    try {
      await api(`match-days/${id}`,{method:'DELETE'})
      reload()
    } catch (err) {
      alert(err.message)
    }
  }
  return (
    <>
      <h2>Match Days</h2>
      <div style={row}>
        <select value={seasonId} onChange={e=>setSeasonId(e.target.value)}><option value=''>Select season…</option>
          {seasons.map(s=><option key={s.id} value={s.id}>{s.name} ({s.year})</option>)}
        </select>
        <input type='number' placeholder='Idx' value={form.idx} onChange={e=>setForm({...form,idx:Number(e.target.value)})}/>
        <input placeholder='Label' value={form.label} onChange={e=>setForm({...form,label:e.target.value})}/>
        <input type='date' value={form.date} onChange={e=>setForm({...form,date:e.target.value})}/>
        <select value={form.type} onChange={e=>setForm({...form,type:e.target.value})}>
          <option value='regular'>regular</option><option value='barrage'>barrage</option><option value='finals'>finals</option>
        </select>
        <button className='button' onClick={add}>Add</button>
      </div>
      <table style={tbl}><thead><tr><th>ID</th><th>Idx</th><th>Label</th><th>Date</th><th>Type</th><th></th></tr></thead>
      <tbody>{rows.map(r=><tr key={r.id}><td>{r.id}</td><td>{r.idx}</td><td>{r.label}</td><td>{r.date}</td><td>{r.type}</td>
        <td><button className='button' onClick={()=>del(r.id)}>Delete</button></td></tr>)}</tbody></table>
    </>
  )
}

function TeamsTab(){
  const [teams,setTeams]=useState([]); const [players,setPlayers]=useState([]); const [teamId,setTeamId]=useState(''); const [tName,setTName]=useState('')
  const refresh=()=>api('teams').then(j=>setTeams(j.rows||[])).catch(console.error)
  useEffect(()=>{refresh()},[])
  const reloadPlayers=()=>{ if(teamId) api(`players?team_id=${teamId}`).then(j=>setPlayers(j.rows||[])).catch(console.error); else setPlayers([]) }
  useEffect(()=>{ reloadPlayers() },[teamId])
  const addTeam=async()=>{
    try {
      await api('teams',{method:'POST',body:JSON.stringify({name:tName})})
      setTName('')
      refresh()
    } catch (err) {
      alert(err.message)
    }
  }
  return (
    <>
      <h2>Teams</h2>
      <div style={row}><input placeholder='Team name' value={tName} onChange={e=>setTName(e.target.value)}/><button className='button' onClick={addTeam}>Add</button></div>
      <select value={teamId} onChange={e=>setTeamId(e.target.value)}><option value=''>Select team…</option>{teams.map(t=><option key={t.id} value={t.id}>{t.name}</option>)}</select>
      <PlayersEditor teamId={teamId} players={players} reload={reloadPlayers}/>
    </>
  )
}
function PlayersEditor({teamId,players,reload}){
  const [form,setForm]=useState({full_name:'',gender:'M',base_hc:0})
  if(!teamId) return null
  const add=async()=>{
    try {
      await api('players',{method:'POST',body:JSON.stringify({...form,team_id:Number(teamId)})})
      setForm({full_name:'',gender:'M',base_hc:0})
      reload()
    } catch (err) {
      alert(err.message)
    }
  }
  const del=async(id)=>{
    try {
      await api(`players/${id}`,{method:'DELETE'})
      reload()
    } catch (err) {
      alert(err.message)
    }
  }
  return (
    <>
      <h3>Players</h3>
      <div style={row}>
        <input placeholder='Full name' value={form.full_name} onChange={e=>setForm({...form,full_name:e.target.value})}/>
        <select value={form.gender} onChange={e=>setForm({...form,gender:e.target.value})}><option>M</option><option>F</option></select>
        <input type='number' placeholder='Base HC' value={form.base_hc} onChange={e=>setForm({...form,base_hc:Number(e.target.value)})}/>
        <button className='button' onClick={add}>Add</button>
      </div>
      <table style={tbl}><thead><tr><th>ID</th><th>Name</th><th>Gender</th><th>BaseHC</th><th></th></tr></thead>
      <tbody>{players.map(p=><tr key={p.id}><td>{p.id}</td><td>{p.full_name}</td><td>{p.gender||''}</td><td>{p.base_hc||0}</td>
        <td><button className='button' onClick={()=>del(p.id)}>Delete</button></td></tr>)}</tbody></table>
    </>
  )
}

function FixturesTab(){
  const [seasons,setSeasons]=useState([]); const [seasonId,setSeasonId]=useState('')
  const [mds,setMds]=useState([]); const [mdId,setMdId]=useState('')
  const [teams,setTeams]=useState([]); const [fx,setFx]=useState([])

  useEffect(()=>{ api('seasons').then(j=>setSeasons(j.rows||[])).catch(console.error); api('teams').then(j=>setTeams(j.rows||[])).catch(console.error) },[])
  useEffect(()=>{ if(seasonId) api(`match-days?season_id=${seasonId}`).then(j=>setMds(j.rows||[])).catch(console.error); setMdId('') },[seasonId])
  useEffect(()=>{ if(seasonId) api(`fixtures-manage?season_id=${seasonId}`).then(j=>setFx(j.rows||[])).catch(console.error) },[seasonId])

  const [form,setForm]=useState({match_day_id:'',lane_id:'',left_team_id:'',right_team_id:''})
  useEffect(()=>{ setForm(f=>({...f,match_day_id:mdId})) },[mdId])

  const add=async()=>{
    if(!form.match_day_id || !form.left_team_id || !form.right_team_id) return alert('Required fields missing')
    try {
      await api('fixtures',{method:'POST',body:JSON.stringify({
        match_day_id:Number(form.match_day_id)||null,
        lane_id:form.lane_id?Number(form.lane_id):null,
        left_team_id:Number(form.left_team_id),
        right_team_id:Number(form.right_team_id)
      })})
      api(`fixtures-manage?season_id=${seasonId}`).then(j=>setFx(j.rows||[])).catch(console.error)
    } catch (err) {
      alert(err.message)
    }
  }

  return (
    <>
      <h2>Program (Fixtures)</h2>
      <div style={row}>
        <select value={seasonId} onChange={e=>setSeasonId(e.target.value)}><option value=''>Season…</option>
          {seasons.map(s=><option key={s.id} value={s.id}>{s.name} ({s.year})</option>)}
        </select>
        <select value={mdId} onChange={e=>setMdId(e.target.value)}><option value=''>Match Day…</option>
          {mds.map(md=><option key={md.id} value={md.id}>{md.idx} — {md.label||md.date}</option>)}
        </select>
        <input type='number' placeholder='Lane' value={form.lane_id} onChange={e=>setForm({...form,lane_id:e.target.value})}/>
        <select value={form.left_team_id} onChange={e=>setForm({...form,left_team_id:e.target.value})}><option value=''>Left team…</option>
          {teams.map(t=><option key={t.id} value={t.id}>{t.name}</option>)}
        </select>
        <select value={form.right_team_id} onChange={e=>setForm({...form,right_team_id:e.target.value})}><option value=''>Right team…</option>
          {teams.map(t=><option key={t.id} value={t.id}>{t.name}</option>)}
        </select>
        <button className='button' onClick={add}>Add Fixture</button>
      </div>

      <table style={tbl}><thead><tr><th>MD</th><th>Lane</th><th>Left</th><th>Right</th><th>Order</th></tr></thead>
      <tbody>{fx.map(f=><FixtureRow key={f.id} f={f} teams={teams}/>)}</tbody></table>
    </>
  )
}
function FixtureRow({f,teams}){
  const [order,setOrder]=useState([])
  const [playersL,setPlayersL]=useState([]); const [playersR,setPlayersR]=useState([])
  useEffect(()=>{ api(`player-order?fixture_id=${f.id}`).then(j=>setOrder(j.rows||[])).catch(console.error) },[f.id])
  useEffect(()=>{
    const leftTeam = teams.find(t=>t.name===f.left_team)
    const rightTeam = teams.find(t=>t.name===f.right_team)
    if (leftTeam) api(`players?team_id=${leftTeam.id}`).then(j=>setPlayersL(j.rows||[])).catch(console.error)
    if (rightTeam) api(`players?team_id=${rightTeam.id}`).then(j=>setPlayersR(j.rows||[])).catch(console.error)
  },[f.id,teams])
  const valueOf=(side,slot)=> order.find(o=>o.team_side===side && o.slot===slot)?.player_id || ''
  const change=(side,slot,val)=>{
    setOrder(prev=>{
      const copy=[...prev]; const i=copy.findIndex(o=>o.team_side===side&&o.slot===slot)
      if(i>=0) copy[i]={...copy[i],player_id:Number(val)}; else copy.push({team_side:side,slot,player_id:Number(val)})
      return copy
    })
  }
  const save=async()=>{
    try {
      await api('player-order',{method:'POST',body:JSON.stringify({fixture_id:f.id,rows:order})})
    } catch (err) {
      alert(err.message)
    }
  }
  return (
    <tr>
      <td>{f.md_idx}</td><td>{f.lane_id||'-'}</td><td>{f.left_team}</td><td>{f.right_team}</td>
      <td>
        <div style={{display:'grid',gridTemplateColumns:'1fr 1fr',gap:6}}>
          {['left','right'].map(side=>(
            <div key={side}>
              <div style={{fontWeight:600,textTransform:'capitalize'}}>{side}</div>
              {[1,2,3].map(slot=>(
                <div key={slot} style={{display:'flex',alignItems:'center',gap:6}}>
                  <span>#{slot}</span>
                  <select value={valueOf(side,slot)} onChange={e=>change(side,slot,e.target.value)}>
                    <option value=''>—</option>
                    {(side==='left'?playersL:playersR).map(p=><option key={p.id} value={p.id}>{p.full_name}</option>)}
                  </select>
                </div>
              ))}
            </div>
          ))}
        </div>
        <button className='button' onClick={save} style={{marginTop:6}}>Save Order</button>
      </td>
    </tr>
  )
}

const row={display:'flex',gap:8,alignItems:'center',margin:'8px 0'}
const tbl={width:'100%',borderCollapse:'collapse'}

const root=document.getElementById('glr-manage-root')
if (root) createRoot(root).render(<ManageApp/>)

