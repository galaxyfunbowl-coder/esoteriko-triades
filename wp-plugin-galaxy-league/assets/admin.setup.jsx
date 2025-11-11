import React, { useEffect, useState } from 'react'
import { createRoot } from 'react-dom/client'

const api = async (path, opt = {}) => {
  const res = await fetch(GLR.rest + path, {
    headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': GLR.nonce },
    ...opt
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

function Setup() {
  const [step, setStep] = useState(1)

  return (
    <div style={{ maxWidth: 1100 }}>
      <Steps current={step} set={setStep} />
      {step === 1 && <StepSeason onNext={() => setStep(2)} />}
      {step === 2 && <StepTeams onNext={() => setStep(3)} />}
      {step === 3 && <StepHC onNext={() => setStep(4)} />}
      {step === 4 && <StepOrder onDone={() => alert('Έτοιμο! Πήγαινε στο Scores για πέρασμα σκορ.')} />}
    </div>
  )
}

function Steps({ current, set }) {
  const items = ['Season', 'Teams/Players', 'HC 1ης', 'Order 1ης']
  return (
    <div style={{ display: 'flex', gap: 8, margin: '8px 0' }}>
      {items.map((t, i) => (
        <button
          key={t}
          className={'button' + (current === i + 1 ? ' button-primary' : '')}
          onClick={() => set(i + 1)}
        >
          {t}
        </button>
      ))}
    </div>
  )
}

function StepSeason({ onNext }) {
  const currentYear = new Date().getFullYear()
  const [name, setName] = useState(`Πρωτάθλημα Τριάδων ${currentYear}-${currentYear + 1}`)
  const [year, setYear] = useState(currentYear)
  const [matchDays, setMatchDays] = useState(7)
  const [seasonId, setSeasonId] = useState('')

  const createSeason = async () => {
    try {
      const res = await api('seasons', {
        method: 'POST',
        body: JSON.stringify({ name, year, total_match_days: matchDays })
      })
      setSeasonId(res.id)
    } catch (err) {
      alert(err.message)
    }
  }

  const createMatchDays = async () => {
    if (!seasonId) {
      alert('Πρώτα φτιάξε Season')
      return
    }
    try {
      const baseDate = new Date()
      for (let i = 1; i <= matchDays; i += 1) {
        const dt = new Date(baseDate.getTime() + (i - 1) * 7 * 24 * 3600 * 1000)
        await api('match-days', {
          method: 'POST',
          body: JSON.stringify({
            season_id: Number(seasonId),
            idx: i,
            label: `${i}η Αγωνιστική`,
            date: dt.toISOString().slice(0, 10),
            type: 'regular'
          })
        })
      }
      alert('Έτοιμες οι αγωνιστικές')
    } catch (err) {
      alert(err.message)
    }
  }

  return (
    <>
      <h2>Βήμα 1: Season & Match Days</h2>
      <div style={row}>
        <input placeholder='Όνομα' value={name} onChange={e => setName(e.target.value)} />
        <input type='number' value={year} onChange={e => setYear(Number(e.target.value))} />
        <input type='number' value={matchDays} onChange={e => setMatchDays(Number(e.target.value))} />
        <button className='button' onClick={createSeason}>
          Create Season
        </button>
        {seasonId && <span>Season ID: {seasonId}</span>}
      </div>
      <div style={row}>
        <button className='button' onClick={createMatchDays} disabled={!seasonId}>
          Create Match Days
        </button>
        <button className='button button-primary' onClick={onNext} disabled={!seasonId}>
          Επόμενο
        </button>
      </div>
    </>
  )
}

function StepTeams({ onNext }) {
  const [teams, setTeams] = useState([])
  const [teamName, setTeamName] = useState('')
  const [teamId, setTeamId] = useState('')
  const [players, setPlayers] = useState([])
  const [playerForm, setPlayerForm] = useState({ full_name: '', gender: 'M', base_hc: 0 })

  const refreshTeams = () =>
    api('teams')
      .then(j => setTeams(j.rows || []))
      .catch(console.error)

  useEffect(() => {
    refreshTeams()
  }, [])

  useEffect(() => {
    if (teamId) {
      api(`players?team_id=${teamId}`).then(j => setPlayers(j.rows || []))
    } else {
      setPlayers([])
    }
  }, [teamId])

  const addTeam = async () => {
    if (!teamName.trim()) return
    try {
      await api('teams', { method: 'POST', body: JSON.stringify({ name: teamName.trim() }) })
      setTeamName('')
      refreshTeams()
    } catch (err) {
      alert(err.message)
    }
  }

  const addPlayer = async () => {
    if (!teamId) {
      alert('Διάλεξε ομάδα')
      return
    }
    if (!playerForm.full_name.trim()) {
      alert('Όνομα παίκτη')
      return
    }
    try {
      await api('players', {
        method: 'POST',
        body: JSON.stringify({
          ...playerForm,
          team_id: Number(teamId),
          full_name: playerForm.full_name.trim()
        })
      })
      setPlayerForm({ full_name: '', gender: 'M', base_hc: 0 })
      api(`players?team_id=${teamId}`)
        .then(j => setPlayers(j.rows || []))
        .catch(console.error)
    } catch (err) {
      alert(err.message)
    }
  }

  return (
    <>
      <h2>Βήμα 2: Ομάδες & Παίκτες</h2>
      <div style={row}>
        <input placeholder='Όνομα ομάδας' value={teamName} onChange={e => setTeamName(e.target.value)} />
        <button className='button' onClick={addTeam}>
          Προσθήκη Ομάδας
        </button>
        <select value={teamId} onChange={e => setTeamId(e.target.value)}>
          <option value=''>— Επιλογή ομάδας —</option>
          {teams.map(t => (
            <option key={t.id} value={t.id}>
              {t.name}
            </option>
          ))}
        </select>
      </div>
      {teamId && (
        <>
          <div style={row}>
            <input
              placeholder='Ονοματεπώνυμο'
              value={playerForm.full_name}
              onChange={e => setPlayerForm({ ...playerForm, full_name: e.target.value })}
            />
            <select value={playerForm.gender} onChange={e => setPlayerForm({ ...playerForm, gender: e.target.value })}>
              <option>M</option>
              <option>F</option>
            </select>
            <input
              type='number'
              placeholder='Base HC (optional)'
              value={playerForm.base_hc}
              onChange={e => setPlayerForm({ ...playerForm, base_hc: Number(e.target.value) })}
            />
            <button className='button' onClick={addPlayer}>
              Προσθήκη Παίκτη
            </button>
          </div>
          <ul>
            {players.map(pl => (
              <li key={pl.id}>
                {pl.full_name} — {pl.gender || '-'}
              </li>
            ))}
          </ul>
        </>
      )}
      <button className='button button-primary' onClick={onNext}>
        Επόμενο
      </button>
    </>
  )
}

function StepHC({ onNext }) {
  const [seasons, setSeasons] = useState([])
  const [seasonId, setSeasonId] = useState('')
  const [matchDays, setMatchDays] = useState([])
  const [matchDayId, setMatchDayId] = useState('')
  const [rows, setRows] = useState([])

  useEffect(() => {
    api('seasons').then(j => setSeasons(j.rows || []))
  }, [])

  useEffect(() => {
    if (seasonId) {
      api(`match-days?season_id=${seasonId}`).then(j => setMatchDays(j.rows || []))
      setMatchDayId('')
      setRows([])
    }
  }, [seasonId])

  useEffect(() => {
    if (matchDayId) {
      api(`hc/list?match_day_id=${matchDayId}`).then(j => setRows(j.rows || []))
    } else {
      setRows([])
    }
  }, [matchDayId])

  const update = (playerId, value) =>
    setRows(prev => prev.map(r => (r.player_id === playerId ? { ...r, handicap: Number(value || 0) } : r)))

  const save = async () => {
    if (!matchDayId) return
    try {
      await api('hc/save', {
        method: 'POST',
        body: JSON.stringify({
          match_day_id: Number(matchDayId),
          rows: rows.map(r => ({ player_id: r.player_id, handicap: Number(r.handicap || 0) }))
        })
      })
      alert('Αποθηκεύτηκαν τα handicap')
    } catch (err) {
      alert(err.message)
    }
  }

  return (
    <>
      <h2>Βήμα 3: Handicap 1ης Αγωνιστικής</h2>
      <div style={row}>
        <select value={seasonId} onChange={e => setSeasonId(e.target.value)}>
          <option value=''>Season…</option>
          {seasons.map(s => (
            <option key={s.id} value={s.id}>
              {s.name} ({s.year})
            </option>
          ))}
        </select>
        <select value={matchDayId} onChange={e => setMatchDayId(e.target.value)}>
          <option value=''>Match Day…</option>
          {matchDays.map(md => (
            <option key={md.id} value={md.id}>
              {md.idx} — {md.label || md.date}
            </option>
          ))}
        </select>
        <button className='button' onClick={save} disabled={!rows.length}>
          Save
        </button>
        <button className='button button-primary' onClick={onNext} disabled={!rows.length}>
          Επόμενο
        </button>
      </div>
      <table style={tbl}>
        <thead>
          <tr>
            <th>Ομάδα</th>
            <th>Παίκτης</th>
            <th>Gender</th>
            <th>HC</th>
          </tr>
        </thead>
        <tbody>
          {rows.map(r => (
            <tr key={r.player_id}>
              <td>{r.team_name}</td>
              <td>{r.full_name}</td>
              <td>{r.gender || ''}</td>
              <td>
                <input type='number' value={r.handicap || 0} onChange={e => update(r.player_id, e.target.value)} style={{ width: 90 }} />
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </>
  )
}

function StepOrder({ onDone }) {
  const [seasons, setSeasons] = useState([])
  const [seasonId, setSeasonId] = useState('')
  const [matchDays, setMatchDays] = useState([])
  const [matchDayId, setMatchDayId] = useState('')
  const [teams, setTeams] = useState([])
  const [fixtures, setFixtures] = useState([])
  const [form, setForm] = useState({ lane_id: '', left_team_id: '', right_team_id: '' })

  useEffect(() => {
    api('seasons').then(j => setSeasons(j.rows || []))
    api('teams').then(j => setTeams(j.rows || []))
  }, [])

  useEffect(() => {
    if (seasonId) {
      api(`match-days?season_id=${seasonId}`).then(j => setMatchDays(j.rows || []))
      api(`fixtures-manage?season_id=${seasonId}`).then(j => setFixtures(j.rows || []))
      setMatchDayId('')
      setForm({ lane_id: '', left_team_id: '', right_team_id: '' })
    } else {
      setMatchDays([])
      setFixtures([])
    }
  }, [seasonId])

  useEffect(() => {
    setForm(prev => ({ ...prev, match_day_id: matchDayId }))
  }, [matchDayId])

  const addFixture = async () => {
    if (!matchDayId || !form.left_team_id || !form.right_team_id) {
      alert('Συμπλήρωσε Match Day και ομάδες')
      return
    }
    try {
      await api('fixtures', {
        method: 'POST',
        body: JSON.stringify({
          match_day_id: Number(matchDayId),
          lane_id: form.lane_id ? Number(form.lane_id) : null,
          left_team_id: Number(form.left_team_id),
          right_team_id: Number(form.right_team_id)
        })
      })
      api(`fixtures-manage?season_id=${seasonId}`)
        .then(j => setFixtures(j.rows || []))
        .catch(console.error)
    } catch (err) {
      alert(err.message)
    }
  }

  return (
    <>
      <h2>Βήμα 4: Πρόγραμμα & Σειρά παικτών (1η)</h2>
      <div style={row}>
        <select value={seasonId} onChange={e => setSeasonId(e.target.value)}>
          <option value=''>Season…</option>
          {seasons.map(s => (
            <option key={s.id} value={s.id}>
              {s.name} ({s.year})
            </option>
          ))}
        </select>
        <select value={matchDayId} onChange={e => setMatchDayId(e.target.value)}>
          <option value=''>Match Day (διάλεξε 1η)…</option>
          {matchDays.map(md => (
            <option key={md.id} value={md.id}>
              {md.idx} — {md.label || md.date}
            </option>
          ))}
        </select>
      </div>

      <div style={row}>
        <input type='number' placeholder='Lane' value={form.lane_id} onChange={e => setForm({ ...form, lane_id: e.target.value })} />
        <select value={form.left_team_id} onChange={e => setForm({ ...form, left_team_id: e.target.value })}>
          <option value=''>Left team…</option>
          {teams.map(t => (
            <option key={t.id} value={t.id}>
              {t.name}
            </option>
          ))}
        </select>
        <select value={form.right_team_id} onChange={e => setForm({ ...form, right_team_id: e.target.value })}>
          <option value=''>Right team…</option>
          {teams.map(t => (
            <option key={t.id} value={t.id}>
              {t.name}
            </option>
          ))}
        </select>
        <button className='button' onClick={addFixture} disabled={!matchDayId}>
          Add Fixture
        </button>
      </div>

      <table style={tbl}>
        <thead>
          <tr>
            <th>MD</th>
            <th>Lane</th>
            <th>Left</th>
            <th>Right</th>
            <th>Order</th>
          </tr>
        </thead>
        <tbody>
          {fixtures
            .filter(f => Number(f.md_idx) === 1)
            .map(f => (
              <FixtureOrderRow key={f.id} fixture={f} teams={teams} />
            ))}
        </tbody>
      </table>

      <button className='button button-primary' onClick={onDone}>
        Τέλος
      </button>
    </>
  )
}

function FixtureOrderRow({ fixture, teams }) {
  const [order, setOrder] = useState([])
  const [leftPlayers, setLeftPlayers] = useState([])
  const [rightPlayers, setRightPlayers] = useState([])

  const findTeamId = name => {
    const team = teams.find(t => t.name === name)
    return team ? team.id : null
  }

  useEffect(() => {
    const leftId = findTeamId(fixture.left_team)
    const rightId = findTeamId(fixture.right_team)
    if (leftId) api(`players?team_id=${leftId}`).then(j => setLeftPlayers(j.rows || [])).catch(console.error)
    if (rightId) api(`players?team_id=${rightId}`).then(j => setRightPlayers(j.rows || [])).catch(console.error)
    api(`player-order?fixture_id=${fixture.id}`).then(j => setOrder(j.rows || [])).catch(console.error)
  }, [fixture.id, teams])

  const valueFor = (side, slot) => order.find(o => o.team_side === side && o.slot === slot)?.player_id || ''

  const change = (side, slot, value) => {
    setOrder(prev => {
      const copy = [...prev]
      const idx = copy.findIndex(o => o.team_side === side && o.slot === slot)
      if (idx >= 0) copy[idx] = { ...copy[idx], player_id: Number(value) }
      else copy.push({ team_side: side, slot, player_id: Number(value) })
      return copy
    })
  }

  const save = async () => {
    try {
      await api('player-order', {
        method: 'POST',
        body: JSON.stringify({ fixture_id: fixture.id, rows: order })
      })
      alert('Saved')
    } catch (err) {
      alert(err.message)
    }
  }

  return (
    <tr>
      <td>{fixture.md_idx}</td>
      <td>{fixture.lane_id || '-'}</td>
      <td>{fixture.left_team}</td>
      <td>{fixture.right_team}</td>
      <td>
        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 6 }}>
          <OrderSide title='Left' players={leftPlayers} side='left' valueFor={valueFor} onChange={change} />
          <OrderSide title='Right' players={rightPlayers} side='right' valueFor={valueFor} onChange={change} />
        </div>
        <button className='button' onClick={save} style={{ marginTop: 6 }}>
          Save Order
        </button>
      </td>
    </tr>
  )
}

function OrderSide({ title, players, side, valueFor, onChange }) {
  return (
    <div>
      <div style={{ fontWeight: 600 }}>{title}</div>
      {[1, 2, 3].map(slot => (
        <div key={slot} style={{ display: 'flex', alignItems: 'center', gap: 6 }}>
          <span>#{slot}</span>
          <select value={valueFor(side, slot)} onChange={e => onChange(side, slot, e.target.value)}>
            <option value=''>—</option>
            {players.map(p => (
              <option key={p.id} value={p.id}>
                {p.full_name}
              </option>
            ))}
          </select>
        </div>
      ))}
    </div>
  )
}

const row = { display: 'flex', gap: 8, alignItems: 'center', margin: '8px 0' }
const tbl = { width: '100%', borderCollapse: 'collapse' }

const root = document.getElementById('glr-setup-root')
if (root) createRoot(root).render(<Setup />)

