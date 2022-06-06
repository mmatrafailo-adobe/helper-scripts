package main

import (
	"bufio"
	"embed"
	"encoding/json"
	"fmt"
	"github.com/joho/godotenv"
	"io/fs"
	"io/ioutil"
	"log"
	"net/http"
	"os"
	"os/exec"
	"sort"
	"strings"
	"time"
)

type Environment struct {
	Name         string    `json:"name"`
	FullPath     string    `json:"fullPath"`
	JiraUrl      string    `json:"jiraUrl"`
	Time         time.Time `json:"time"`
	ContainerUrl string    `json:"containerUrl"`
	IsRunning    bool      `json:"isRunning"`
}

type Environments struct {
	environments []Environment `json:"environments"`
}

func (c *Environments) Add(environment Environment) {
	c.environments = append(c.environments, environment)
}

func (c *Environments) GetByName(name string) (environment Environment, ok bool) {
	for _, env := range c.environments {
		if env.Name == name {
			return env, true
		}
	}

	return Environment{}, false
}

func (c *Environments) SortByTime() {
	sort.Slice(c.environments, func(i, j int) bool {
		return c.environments[i].Time.After(c.environments[j].Time)
	})
}
func (c *Environments) GetAll() []Environment {
	return c.environments
}

//go:embed static/*
var staticDir embed.FS

const CLIENTS_DIR = "/Users/npuchko/www/clients"
const DEFAULT_PORT = "8080"

func main() {
	dirForClientDumps := CLIENTS_DIR
	webserverPort := DEFAULT_PORT
	settings, err := godotenv.Read("./.env")
	if err == nil {
		fmt.Println("Configs from .env file loaded")
		dirForClientDumps = settings["CLIENTS_DIR"]
		webserverPort = settings["DEFAULT_PORT"]
	}

	http.Handle("/", http.FileServer(getStatic()))
	http.HandleFunc("/list-envs", func(w http.ResponseWriter, r *http.Request) {
		//fmt.Fprintf(w, "Hello, %q", html.EscapeString(r.URL.Path))
		environments := getListOfEnvironments(dirForClientDumps)
		environments.SortByTime()
		json.NewEncoder(w).Encode(environments.GetAll())
	})

	http.HandleFunc("/download-env", func(w http.ResponseWriter, r *http.Request) {
		//fmt.Fprintf(w, "Hello, %q", html.EscapeString(r.URL.Path))

		keys, ok := r.URL.Query()["name"]
		if !ok || len(keys[0]) < 1 {
			fmt.Fprintf(w, "Url Param 'name' is missing")
			return
		}
		projectIds, ok := r.URL.Query()["project_id"]
		if !ok || len(keys[0]) < 1 {
			fmt.Fprintf(w, "Url Param 'project_id' is missing")
			return
		}

		name := keys[0]

		environments := getListOfEnvironments(dirForClientDumps)

		_, found := environments.GetByName(name)
		if found == true {
			fmt.Fprintf(w, "Env with name "+name+" already exists")
			return
		}

		fmt.Fprintf(w, "Downloaded")
		return

		cmd := exec.Command("wdi", name, projectIds[0])
		cmdReader, _ := cmd.StdoutPipe()
		scanner := bufio.NewScanner(cmdReader)
		done := make(chan bool)
		go func() {
			for scanner.Scan() {
				fmt.Printf(scanner.Text())
			}
			done <- true
		}()
		cmd.Start()
		<-done
		err := cmd.Wait()
		if err != nil {
			fmt.Printf("Error with command")
		}
	})

	http.HandleFunc("/stop", func(w http.ResponseWriter, r *http.Request) {

		keys, ok := r.URL.Query()["name"]

		if !ok || len(keys[0]) < 1 {
			fmt.Fprintf(w, "Url Param 'name' is missing")
			return
		}
		name := keys[0]

		environments := getListOfEnvironments(dirForClientDumps)

		env, found := environments.GetByName(name)
		if found != true {
			fmt.Fprintf(w, "I did not find any env with name "+name)
			return
		}

		cmd := exec.Command("warden", "env", "down")
		cmd.Dir = env.FullPath
		out, err := cmd.CombinedOutput()
		if err != nil {
			fmt.Fprintf(w, "ERROR on server")
		} else {
			fmt.Fprintf(w, "Env "+name+" stopped "+string(out))
		}

	})

	http.HandleFunc("/start", func(w http.ResponseWriter, r *http.Request) {
		keys, ok := r.URL.Query()["name"]

		if !ok || len(keys[0]) < 1 {
			fmt.Fprintf(w, "Url Param 'name' is missing")
			return
		}
		name := keys[0]

		environments := getListOfEnvironments(dirForClientDumps)
		env, found := environments.GetByName(name)
		if found != true {
			fmt.Fprintf(w, "I did not find any env with name "+name)
			return
		}

		cmd := exec.Command("warden", "env", "up")
		cmd.Dir = env.FullPath
		out, err := cmd.CombinedOutput()
		if err != nil {
			fmt.Fprintf(w, "ERROR on server")
		} else {
			fmt.Fprintf(w, "Env "+name+" started: "+string(out))
		}
	})
	http.HandleFunc("/remove", func(w http.ResponseWriter, r *http.Request) {
		keys, ok := r.URL.Query()["name"]

		if !ok || len(keys[0]) < 1 {
			fmt.Fprintf(w, "Url Param 'name' is missing")
			return
		}
		name := keys[0]

		environments := getListOfEnvironments(dirForClientDumps)
		env, found := environments.GetByName(name)
		if found != true {
			fmt.Fprintf(w, "I did not find any env with name "+name)
			return
		}

		if env.IsRunning {
			fmt.Fprintf(w, "Env is running, STOP first "+name)
			return
		}

		cmd := exec.Command("warden-remove", name)
		cmd.Dir = dirForClientDumps
		out, err := cmd.CombinedOutput()
		if err != nil {
			fmt.Fprintf(w, "ERROR on server")
		} else {
			fmt.Fprintf(w, "Env "+name+" removed successfully: \r\n"+string(out))
		}

		fmt.Fprintf(w, "Env "+name+" removed")
	})

	fmt.Printf("Starting server at port http://localhost:" + webserverPort + "\n")
	if err := http.ListenAndServe(":"+webserverPort, nil); err != nil {
		log.Fatal(err)
	}

}

func getStatic() http.FileSystem {
	static, err := fs.Sub(staticDir, "static")
	if err != nil {
		log.Fatal(err)
	}

	return http.FS(static)
}

func getListOfEnvironments(dir string) Environments {
	files, err := ioutil.ReadDir(dir)
	if err != nil {
		log.Fatal(err)
	}

	environments := Environments{}

	for _, f := range files {
		envFilePath := dir + "/" + f.Name() + "/.env"
		if !f.IsDir() {
			continue
		}
		containerUrl := ""
		isRunning := false

		if _, err := os.Stat(envFilePath); err == nil {
			envData, err := godotenv.Read(dir + "/" + f.Name() + "/.env")
			if err == nil {
				containerUrl = "https://" + envData["TRAEFIK_SUBDOMAIN"] + "." + envData["TRAEFIK_DOMAIN"] + "/"
				command := "docker ps -f name=" + f.Name() + "_"
				out, err := exec.Command("bash", "-c", command).CombinedOutput()
				if err != nil {
					fmt.Println("Failed to execute command: %s", command)
				} else {
					if strings.Contains(string(out), f.Name()) {
						isRunning = true
					}
				}
			}
		}

		ticketNumber := strings.Replace(f.Name(), "mdva", "MDVA-", 1)
		environment := Environment{
			Name:         f.Name(),
			FullPath:     dir + "/" + f.Name(),
			JiraUrl:      "https://jira.corp.magento.com/browse/" + ticketNumber,
			Time:         f.ModTime(),
			ContainerUrl: containerUrl,
			IsRunning:    isRunning,
		}

		environments.Add(environment)
	}

	return environments
}
