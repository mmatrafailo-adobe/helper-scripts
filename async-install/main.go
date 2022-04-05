package main

import (
	"fmt"
	"github.com/google/uuid"
	"io/ioutil"
	"log"
	"os"
	"os/exec"
	"path/filepath"
	"regexp"
	"strings"
	"sync"
	"time"
)

var wg sync.WaitGroup

func sum(a int, b int, c chan int) {
	//defer wg.Done()
	c <- a + b // send sum to c
	c <- a - b // send sum to c
	close(c)
}

func runCommandWithChannel(command string, dir string, msg chan string) {
	commandObject := exec.Command("bash", "-c", command)
	commandObject.Dir = dir
	out, err := commandObject.CombinedOutput()
	msg <- string(out)
	if err != nil {
		msg <- "Failed to execute command " + command + " Error: " + err.Error()
	}
}

func createFolderAndInitProject(dir string, projectName string, jiraTicketUrl string, msg chan string) {
	os.Mkdir(dir, 0777)

	runCommandWithChannel("warden env-init "+projectName+" magento2", dir, msg)
	runCommandWithChannel("warden sign-certificate "+projectName+".test", dir, msg)

	if jiraTicketUrl != "" {
		f, err := os.OpenFile(dir+"/.env", os.O_APPEND|os.O_WRONLY, 0644)
		defer f.Close()
		if err == nil {
			f.WriteString("JIRA_TICKET_URL=" + jiraTicketUrl)
		} else {
			msg <- "error with writing .env file: " + err.Error()
		}
	}

	runCommandWithChannel("warden env up", dir, msg)

}

func makeIdeaFolder(dir string) {

	ideaFolderPath := dir + "/.idea"
	os.Mkdir(ideaFolderPath, 0777)
	uuidWithHyphen := uuid.New()

	dataSources := ""

	ioutil.WriteFile(ideaFolderPath+"/dataSources.xml", []byte(data), 0664)
}

func getProjectNameFromJIRAURL(jiraUrl string) string {
	// take last path part jira.com/browse/MDVA-1234?fwefewf=ewf => MDVA-1234
	parts := strings.Split(jiraUrl, "/")
	projectNamePart := parts[len(parts)-1]
	if strings.Contains(projectNamePart, "?") {
		parts = strings.Split(projectNamePart, "?")
		projectNamePart = parts[0]
	}

	// remove all except letters and numbers
	reg, err := regexp.Compile("[^a-zA-Z0-9]+")
	if err != nil {
		log.Fatal(err)
	}
	projectNamePart = reg.ReplaceAllString(projectNamePart, "")

	return projectNamePart
}

func main() {
	start := time.Now()

	currentDir, err := os.Getwd()
	if err != nil {
		panic(err)
	}

	fmt.Print("Enter JIRA url or folder name (skip if it is a current dir): ")
	var jiraTicketUrl string
	fmt.Scan(jiraTicketUrl)

	var projectNameAndDir string
	if jiraTicketUrl == "" {
		projectNameAndDir = filepath.Base(currentDir)
	} else if strings.Contains(projectNameAndDir, "http") || strings.Contains(projectNameAndDir, "/") {
		projectNameAndDir = getProjectNameFromJIRAURL(jiraTicketUrl)
	} else {
		projectNameAndDir = getProjectNameFromJIRAURL(jiraTicketUrl)
		jiraTicketUrl = ""
	}

	fmt.Print("Enter PROJECT ID: ")
	var projectId string
	fmt.Scan(&projectId)

	if projectId != "" {
		fmt.Println("Project id is " + projectId)

		listEnvsString := runCommand(" magento-cloud environment:list --project " + projectId + " --pipe")
		fmt.Println(listEnvsString)

		var envId string
		fmt.Print("Enter env id: ")
		fmt.Scan(&envId)

		// ucxdbrjol65si
		sshPath := runCommand("magento-cloud env:ssh --project " + projectId + " --environment " + envId + " --pipe")

		fmt.Println(sshPath)
	} else {
		fmt.Println("Project ID is empty, Skipping downloading the dumps")
	}

	//c := make(chan int)
	////wg.Add(1)
	//go sum(20, 22, c)
	////wg.Add(1)
	////go sum(21, 24, c)
	////wg.Wait()
	//
	//for i := range c {
	//
	//	fmt.Println(i)
	//}
	////x := <-c // receive from c
	////
	////fmt.Println(x)

	elapsed := time.Since(start)
	fmt.Printf("Time in runtime %s\n", elapsed)
}

func runCommand(command string) (output string) {
	out, err := exec.Command("bash", "-c", command).CombinedOutput()
	if err != nil {
		fmt.Println("Failed to execute command: %s \n error is: ", command, err)
		panic(err)
	}

	return string(out)
}
