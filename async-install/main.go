package main

import (
	"fmt"
	"os/exec"
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

func main() {
	start := time.Now()

	fmt.Print("Enter PROJECT ID: ")
	var projectId string
	fmt.Scan(&projectId)

	fmt.Println("Project id is " + projectId)

	listEnvsString := runCommand(" magento-cloud environment:list --project " + projectId + " --pipe")
	fmt.Println(listEnvsString)

	var envId string
	fmt.Print("Enter env id: ")
	fmt.Scan(&envId)

	// ucxdbrjol65si
	sshPath := runCommand("magento-cloud env:ssh --project " + projectId + " --environment " + envId + " --pipe")

	fmt.Println(sshPath)

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
