import { defineConfig, splitVendorChunkPlugin } from "vite"

import react from "@vitejs/plugin-react-swc"
import liveReload from "vite-plugin-live-reload"

import path from "node:path"

export default defineConfig({
    plugins: [
        react(),
        liveReload([
            __dirname + "/res/**/*.php",
            __dirname + "/dist/*.php",
        ]),
        splitVendorChunkPlugin(),
    ],

    root: "res",
    base: "./",

    build: {
        outDir: "../dist",
        emptyOutDir: false,
        manifest: true,
        rollupOptions: {
            input: path.resolve(__dirname, "res/main.jsx"),
        },
        assetsInlineLimit: filePath => filePath.endsWith('.svg') ? false : 4096,
    },

    server: {
        strictPort: true,
        port: 3000,
        origin: "http://localhost:3000"
    }
})