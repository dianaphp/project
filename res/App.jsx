import { useEffect, useState } from 'react'
import reactLogo from './assets/react.svg'
import viteLogo from './assets/vite.svg'
import dianaLogo from './assets/diana.svg'
import './App.css'

function App() {
    const [data, setData] = useState(null);
    const [status, setStatus] = useState("Loading...");

    useEffect(() => {
        const request = new XMLHttpRequest();
        request.open("GET", "/data");
        request.onload = () => {
            if (request.status == 200)
                setData(JSON.parse(request.responseText))
            else
                setStatus("An error occured.");
        };
        request.send();
    }, [])

    return (
        <>
            <div>
                <a href="https://github.com/dianaphp" target="_blank">
                    <img src={dianaLogo} className="logo" alt="Diana logo" />
                </a>
                <a href="https://vitejs.dev" target="_blank">
                    <img src={viteLogo} className="logo" alt="Vite logo" />
                </a>
                <a href="https://react.dev" target="_blank">
                    <img src={reactLogo} className="logo react" alt="React logo" />
                </a>
            </div>
            <h1>{data?.name || "Diana Application"}</h1>
            <p className="read-the-docs">
                {data ? <>Diana {data?.dianaVersion} / PHP {data?.phpVersion}</> : status}
            </p>
        </>
    )
}

export default App